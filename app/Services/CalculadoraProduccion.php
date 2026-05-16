<?php

namespace App\Services;

/**
 * CalculadoraProduccion
 *
 * Orquestador principal del motor de cálculo.
 *
 * Los avisos llevan ahora referencia a su contexto:
 *   - alcance: 'tarea' | 'receta' | 'global'
 *   - referencia_id: ID de la tarea o receta a la que aplica
 *   - id: identificador único del aviso (para scroll-to desde el resumen)
 */
class CalculadoraProduccion
{
    public function __construct(
        private RepositorioDatos $repo,
        private GestorAlergenos $alergenos,
        private PlanificadorTemporal $planificador,
    ) {}

    public function calcular(string $escenarioId, array $recetasIds, ?array $escenarioOverride = null): array
    {
        $escenario = $escenarioOverride ?? $this->repo->escenarioPorId($escenarioId);
        if (!$escenario) {
            return ['error' => "Escenario no encontrado: {$escenarioId}"];
        }

        $recetas = $this->repo->recetasPorIds($recetasIds);
        if (empty($recetas)) {
            return ['error' => 'No se han seleccionado recetas válidas'];
        }

        $bloquesPorReceta = [];
        $tieneAdaptaciones = false;

        foreach ($recetas as $receta) {
            $reparto = $this->alergenos->calcularRaciones($receta, $escenario);

            $ingredientesEstandar = $this->escalarIngredientes(
                $receta['ingredientes'],
                $reparto['estandar']
            );

            $versionesAdaptadas = [];
            foreach ($reparto['adaptaciones'] as $adaptacion) {
                if ($adaptacion['sin_solucion']) {
                    $versionesAdaptadas[] = [
                        'alergenos_evitados' => $adaptacion['alergenos_evitados'],
                        'raciones' => $adaptacion['raciones'],
                        'sin_solucion' => true,
                        'aviso' => $adaptacion['aviso'] ?? '',
                    ];
                    continue;
                }

                $ingAdaptados = $this->alergenos->aplicarSustituciones(
                    $receta,
                    $adaptacion['alergenos_evitados']
                );
                $versionesAdaptadas[] = [
                    'alergenos_evitados' => $adaptacion['alergenos_evitados'],
                    'raciones' => $adaptacion['raciones'],
                    'sin_solucion' => false,
                    'ingredientes' => $this->escalarIngredientes($ingAdaptados, $adaptacion['raciones']),
                ];
                $tieneAdaptaciones = true;
            }

            $bloquesPorReceta[] = [
                'id' => $receta['id'],
                'nombre' => $receta['nombre'],
                'categoria' => $receta['categoria'],
                'raciones_estandar' => $reparto['estandar'],
                'raciones_adaptadas_total' => array_sum(array_column($reparto['adaptaciones'], 'raciones')),
                'ingredientes_estandar' => $ingredientesEstandar,
                'versiones_adaptadas' => $versionesAdaptadas,
            ];
        }

        $miseEnPlace = $this->generarMiseEnPlace($bloquesPorReceta);

        $tareas = $this->planificador->planificar(
            $recetas,
            $escenario['ocupacion']['total_comensales'],
            $escenario['hora_servicio'],
            $tieneAdaptaciones
        );

        $avisos = $this->generarAvisos($bloquesPorReceta, $tareas, $escenario);

        $totalComensales = $escenario['ocupacion']['total_comensales'];
        $totalAdaptados = array_sum(array_column($bloquesPorReceta, 'raciones_adaptadas_total'));
        $totalEstandar = $totalComensales - $totalAdaptados;

        return [
            'escenario' => $escenario,
            'recetas' => $bloquesPorReceta,
            'mise_en_place' => $miseEnPlace,
            'planificacion' => $tareas,
            'avisos' => $avisos,
            'resumen' => [
                'total_comensales' => $totalComensales,
                'raciones_estandar' => $totalEstandar,
                'raciones_adaptadas' => $totalAdaptados,
                'hora_servicio' => $escenario['hora_servicio'],
                'hora_inicio' => $this->planificador->horaInicioGlobal($tareas),
                'numero_recetas' => count($recetas),
            ],
        ];
    }

    private function escalarIngredientes(array $ingredientes, int $raciones): array
    {
        $resultado = [];
        foreach ($ingredientes as $ing) {
            $cantidadBase = $ing['cantidad'] * $raciones;
            $merma = $ing['merma'] ?? 0;
            $cantidadConMerma = $merma > 0
                ? $cantidadBase / (1 - $merma)
                : $cantidadBase;

            [$cantidadFormateada, $unidadFormateada] = $this->formatearCantidad(
                $cantidadConMerma,
                $ing['unidad']
            );

            $resultado[] = [
                'nombre' => $ing['nombre'],
                'cantidad' => $cantidadFormateada,
                'unidad' => $unidadFormateada,
                'cantidad_bruta' => round($cantidadConMerma, 2),
                'unidad_bruta' => $ing['unidad'],
                'sustituido' => $ing['sustituido'] ?? false,
                'ingrediente_original' => $ing['ingrediente_original'] ?? null,
            ];
        }
        return $resultado;
    }

    private function generarMiseEnPlace(array $bloquesPorReceta): array
    {
        $agregado = [];

        foreach ($bloquesPorReceta as $bloque) {
            foreach ($bloque['ingredientes_estandar'] as $ing) {
                $this->acumularIngrediente($agregado, $ing, false, [
                    'receta_id' => $bloque['id'],
                    'receta_nombre' => $bloque['nombre'],
                    'version' => 'estandar',
                ]);
            }

            foreach ($bloque['versiones_adaptadas'] as $version) {
                if ($version['sin_solucion'] ?? false) {
                    continue;
                }
                foreach ($version['ingredientes'] as $ing) {
                    $this->acumularIngrediente(
                        $agregado,
                        $ing,
                        $ing['sustituido'] ?? false,
                        [
                            'receta_id' => $bloque['id'],
                            'receta_nombre' => $bloque['nombre'],
                            'version' => 'sin ' . implode('/', $version['alergenos_evitados']),
                        ]
                    );
                }
            }
        }

        $resultado = [];
        foreach ($agregado as $datos) {
            [$cantidadFormateada, $unidadFormateada] = $this->formatearCantidad(
                $datos['cantidad_bruta'],
                $datos['unidad_bruta']
            );
            $resultado[] = [
                'nombre' => $datos['nombre'],
                'cantidad' => $cantidadFormateada,
                'cantidad_bruta' => $datos['cantidad_bruta'],
                'unidad' => $unidadFormateada,
                'es_sustituto' => $datos['es_sustituto'],
                'usado_en' => $datos['usado_en'],
            ];
        }

        usort($resultado, function ($a, $b) {
            if ($a['es_sustituto'] !== $b['es_sustituto']) {
                return $a['es_sustituto'] ? 1 : -1;
            }
            return strcmp($a['nombre'], $b['nombre']);
        });

        return $resultado;
    }

    private function acumularIngrediente(array &$agregado, array $ing, bool $esSustituto, array $uso): void
    {
        $clave = $ing['nombre'] . '|' . $ing['unidad_bruta'];

        if (!isset($agregado[$clave])) {
            $agregado[$clave] = [
                'nombre' => $ing['nombre'],
                'cantidad_bruta' => 0,
                'unidad_bruta' => $ing['unidad_bruta'],
                'es_sustituto' => $esSustituto,
                'usado_en' => [],
            ];
        }

        $agregado[$clave]['cantidad_bruta'] += $ing['cantidad_bruta'];
        $agregado[$clave]['usado_en'][] = $uso;

        if ($esSustituto) {
            $agregado[$clave]['es_sustituto'] = true;
        }
    }

    private function formatearCantidad(float $cantidad, string $unidad): array
    {
        if ($unidad === 'g' && $cantidad >= 1000) {
            return [$this->formatoNumero($cantidad / 1000, 2), 'kg'];
        }
        if ($unidad === 'ml' && $cantidad >= 1000) {
            return [$this->formatoNumero($cantidad / 1000, 2), 'L'];
        }
        if ($unidad === 'g' || $unidad === 'ml') {
            return [$this->formatoNumero($cantidad, 0), $unidad];
        }
        return [$this->formatoNumero($cantidad, 2), $unidad];
    }

    private function formatoNumero(float $valor, int $decimales): string
    {
        return number_format($valor, $decimales, ',', '.');
    }

    /**
     * Genera la lista de avisos.
     *
     * Cada aviso lleva:
     *   - id: identificador único (para scroll-to desde el resumen)
     *   - tipo: 'danger' | 'warn' | 'ok'
     *   - titulo, mensaje
     *   - alcance: 'tarea' | 'receta' | 'global'
     *   - referencia_id: ID de la tarea (elaboracion_id) o receta a la que aplica
     *   - referencia_nombre: nombre legible de la referencia
     */
    private function generarAvisos(array $recetas, array $tareas, array $escenario): array
    {
        $avisos = [];
        $contador = 0;

        // 1. Versiones adaptadas sin solución (por receta)
        foreach ($recetas as $bloque) {
            foreach ($bloque['versiones_adaptadas'] as $adap) {
                if (!empty($adap['sin_solucion'])) {
                    $alergs = implode(', ', $adap['alergenos_evitados']);
                    $avisos[] = [
                        'id' => 'aviso-' . (++$contador),
                        'tipo' => 'danger',
                        'titulo' => 'Sin sustitución disponible',
                        'mensaje' => "Requiere adaptación para {$alergs} pero no hay sustitutos definidos. {$adap['raciones']} comensales sin solución.",
                        'alcance' => 'receta',
                        'referencia_id' => $bloque['id'],
                        'referencia_nombre' => $bloque['nombre'],
                    ];
                }
            }
        }

        // 2. Contaminación cruzada (por tarea: las de montaje y cocción de recetas con adaptación)
        $idsRecetasConAdaptacion = [];
        foreach ($recetas as $bloque) {
            if ($bloque['raciones_adaptadas_total'] > 0) {
                $idsRecetasConAdaptacion[] = $bloque['id'];
            }
        }
        if (!empty($idsRecetasConAdaptacion)) {
            foreach ($tareas as $tarea) {
                if (!in_array($tarea['receta_id'] ?? null, $idsRecetasConAdaptacion, true)) {
                    continue;
                }
                if (!in_array($tarea['tipo'] ?? '', ['montaje', 'coccion'], true)) {
                    continue;
                }
                $avisos[] = [
                    'id' => 'aviso-' . (++$contador),
                    'tipo' => 'danger',
                    'titulo' => 'Contaminación cruzada',
                    'mensaje' => 'Usar superficie, utensilios y bandeja diferenciados para la versión adaptada. Marcar con etiqueta antes del horneado.',
                    'alcance' => 'tarea',
                    'referencia_id' => $tarea['elaboracion_id'],
                    'referencia_nombre' => $tarea['descripcion'],
                ];
            }
        }

        // 3. Tandas múltiples (por tarea)
        foreach ($tareas as $tarea) {
            if (($tarea['tandas'] ?? 1) > 1 && !($tarea['es_final'] ?? false)) {
                $cap = $tarea['capacidad_equipo'];
                $total = $tarea['raciones_total'];
                $tandas = $tarea['tandas'];
                $avisos[] = [
                    'id' => 'aviso-' . (++$contador),
                    'tipo' => 'warn',
                    'titulo' => "Producción en {$tandas} tandas",
                    'mensaje' => "El equipo admite {$cap} raciones por tanda, se necesitan {$tandas} tandas para producir {$total} raciones. Coordina las salidas con el servicio.",
                    'alcance' => 'tarea',
                    'referencia_id' => $tarea['elaboracion_id'],
                    'referencia_nombre' => $tarea['descripcion'],
                ];
            }
        }

        // 4. Elaboraciones compartidas (por receta)
        $compartibles = $this->detectarElaboracionesCompartidas($recetas);
        foreach ($compartibles as $info) {
            $avisos[] = [
                'id' => 'aviso-' . (++$contador),
                'tipo' => 'ok',
                'titulo' => 'Elaboración común',
                'mensaje' => "{$info['descripcion']} se puede elaborar conjuntamente con: " . implode(', ', $info['recetas_compartidas']),
                'alcance' => 'receta',
                'referencia_id' => $info['receta_id'],
                'referencia_nombre' => $info['receta_nombre'],
            ];
        }

        return $avisos;
    }

    /**
     * Detecta elaboraciones compartidas. Devuelve para cada receta los avisos
     * de qué preparaciones comparte con qué otras recetas.
     */
    private function detectarElaboracionesCompartidas(array $bloques): array
    {
        $idsSeleccionadas = array_column($bloques, 'id');
        $nombresPorId = array_column($bloques, 'nombre', 'id');
        $resultado = [];
        $ya = []; // evitar duplicados

        foreach ($bloques as $bloque) {
            $receta = $this->repo->recetaPorId($bloque['id']);
            foreach ($receta['elaboraciones'] as $elab) {
                $compartibleCon = $elab['compartible_con'] ?? [];
                $intersect = array_values(array_intersect($compartibleCon, $idsSeleccionadas));
                if (empty($intersect)) {
                    continue;
                }
                $clave = $bloque['id'] . '|' . $elab['descripcion'];
                if (isset($ya[$clave])) {
                    continue;
                }
                $ya[$clave] = true;
                $resultado[] = [
                    'receta_id' => $bloque['id'],
                    'receta_nombre' => $bloque['nombre'],
                    'descripcion' => $elab['descripcion'],
                    'recetas_compartidas' => array_map(fn($id) => $nombresPorId[$id] ?? $id, $intersect),
                ];
            }
        }
        return $resultado;
    }
}
