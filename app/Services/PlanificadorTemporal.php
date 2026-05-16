<?php

namespace App\Services;

/**
 * PlanificadorTemporal
 *
 * Calcula la planificación temporal de la producción:
 * - Determina si una elaboración requiere varias tandas según capacidad del equipo
 * - Calcula la hora de inicio de cada tarea trabajando hacia atrás desde la hora de servicio
 * - Detecta elaboraciones compartibles entre recetas (para futuro)
 */
class PlanificadorTemporal
{
    public function __construct(private RepositorioDatos $repo) {}

    /**
     * Planifica la producción para una o varias recetas, dado el total de raciones
     * (suma de estándar + adaptadas) y la hora de servicio.
     *
     * Devuelve un array con las tareas ordenadas cronológicamente, cada una con:
     * - 'hora_inicio': "HH:MM"
     * - 'descripcion': texto
     * - 'equipo': nombre del equipo
     * - 'duracion_minutos': int
     * - 'tandas': int (1 si entra de una vez, >1 si requiere tandas)
     * - 'tiene_versiones': bool (true si se hace en estándar + adaptada)
     * - 'receta_id': ID de la receta a la que pertenece
     *
     * @param array $recetas  Lista de recetas seleccionadas
     * @param int $racionesTotales  Suma total de raciones a producir
     * @param string $horaServicio  "HH:MM"
     * @param bool $tieneVersiones  Si se hacen versiones adaptadas además de la estándar
     * @return array
     */
    public function planificar(array $recetas, int $racionesTotales, string $horaServicio, bool $tieneVersiones = false): array
    {
        $tareas = [];

        foreach ($recetas as $receta) {
            foreach ($receta['elaboraciones'] as $elab) {
                $equipo = $this->repo->equipoPorTipo($elab['equipo']);
                $capacidad = $equipo['capacidad_raciones'] ?? 999;
                $tandas = (int) ceil($racionesTotales / $capacidad);

                $tareas[] = [
                    'receta_id' => $receta['id'],
                    'receta_nombre' => $receta['nombre'],
                    'elaboracion_id' => $elab['id'],
                    'descripcion' => $elab['descripcion'],
                    'equipo_tipo' => $elab['equipo'],
                    'equipo_nombre' => $equipo['nombre'] ?? $elab['equipo'],
                    'duracion_minutos' => $elab['tiempo_minutos'],
                    'tipo' => $elab['tipo'],
                    'temperatura' => $elab['temperatura'] ?? null,
                    'tandas' => $tandas,
                    'tiene_versiones' => $tieneVersiones && $this->elaboracionAfectaSustitucion($elab, $receta),
                    'capacidad_equipo' => $capacidad,
                    'raciones_total' => $racionesTotales,
                ];
            }
        }

        // Calcular hora de inicio: vamos hacia atrás desde la hora de servicio
        $tareas = $this->calcularHorasInicio($tareas, $horaServicio);

        // Añadir tarea final "Listo para servicio"
        $tareas[] = [
            'receta_id' => null,
            'receta_nombre' => null,
            'elaboracion_id' => null,
            'descripcion' => 'Listo para servicio',
            'equipo_tipo' => null,
            'equipo_nombre' => null,
            'duracion_minutos' => 0,
            'tipo' => 'final',
            'temperatura' => null,
            'tandas' => 1,
            'tiene_versiones' => false,
            'hora_inicio' => $horaServicio,
            'es_final' => true,
        ];

        return $tareas;
    }

    /**
     * Decide si una elaboración se ve afectada por las sustituciones de alérgenos.
     * Si alguno de los ingredientes que se sustituye aparece en la elaboración, sí lo afecta.
     * Por simplicidad en demo, asumimos que las preparaciones (bechamel, sofrito) sí
     * requieren versión adaptada, y los acabados también. Los reposos en frío no.
     */
    private function elaboracionAfectaSustitucion(array $elab, array $receta): bool
    {
        // Si la receta no tiene sustituciones, ninguna elaboración tiene versión
        if (empty($receta['sustituciones_alergenos'])) {
            return false;
        }
        // Los reposos en frío no se duplican
        if (($elab['tipo'] ?? '') === 'reposo') {
            return false;
        }
        return true;
    }

    /**
     * Asigna hora de inicio a cada tarea, trabajando hacia atrás desde la hora de servicio.
     * Considera dependencias secuenciales: cada elaboración empieza cuando termina la anterior
     * en su receta. Para coccion en horno con tandas, la última tanda debe terminar a la hora.
     */
    private function calcularHorasInicio(array $tareas, string $horaServicio): array
    {
        // Agrupar tareas por receta para procesar la secuencia de cada una
        $porReceta = [];
        foreach ($tareas as $idx => $tarea) {
            $porReceta[$tarea['receta_id']][] = $idx;
        }

        // Para cada receta, calcular las horas en cadena inversa
        foreach ($porReceta as $recetaId => $indices) {
            $duracionTotal = 0;

            // Sumar todas las duraciones para saber cuándo empezar (en minutos antes del servicio)
            // Si una tarea tiene varias tandas, la duración efectiva es:
            // duracion * tandas (en horno, las tandas se hacen secuencialmente)
            foreach ($indices as $idx) {
                $tarea = $tareas[$idx];
                if ($tarea['equipo_tipo'] === 'horno') {
                    // En horno, cada tanda es secuencial
                    $duracionTotal += $tarea['duracion_minutos'] * $tarea['tandas'];
                } else {
                    $duracionTotal += $tarea['duracion_minutos'];
                }
            }

            // Ahora asignar hora de inicio a cada tarea de la receta
            $minutosAcumulados = 0;
            $horaInicioReceta = $this->restarMinutos($horaServicio, $duracionTotal);

            foreach ($indices as $idx) {
                $tareas[$idx]['hora_inicio'] = $this->sumarMinutos($horaInicioReceta, $minutosAcumulados);

                if ($tareas[$idx]['equipo_tipo'] === 'horno') {
                    $minutosAcumulados += $tareas[$idx]['duracion_minutos'] * $tareas[$idx]['tandas'];
                } else {
                    $minutosAcumulados += $tareas[$idx]['duracion_minutos'];
                }
            }
        }

        // Ordenar cronológicamente
        usort($tareas, fn($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));

        return $tareas;
    }

    /**
     * Devuelve la hora de inicio absoluta (primera tarea) entre todas las recetas.
     */
    public function horaInicioGlobal(array $tareasOrdenadas): string
    {
        foreach ($tareasOrdenadas as $tarea) {
            if (isset($tarea['hora_inicio']) && !($tarea['es_final'] ?? false)) {
                return $tarea['hora_inicio'];
            }
        }
        return '';
    }

    private function restarMinutos(string $hora, int $minutos): string
    {
        $ts = strtotime($hora);
        $ts -= $minutos * 60;
        return date('H:i', $ts);
    }

    private function sumarMinutos(string $hora, int $minutos): string
    {
        $ts = strtotime($hora);
        $ts += $minutos * 60;
        return date('H:i', $ts);
    }
}
