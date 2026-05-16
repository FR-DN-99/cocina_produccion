<?php

namespace App\Services;

/**
 * GestorAlergenos
 *
 * Calcula cómo se reparten las raciones entre versión estándar y versión adaptada
 * cuando hay alérgicos en el escenario, y aplica las sustituciones correspondientes.
 */
class GestorAlergenos
{
    /**
     * Calcula los grupos de raciones para una receta dado un escenario.
     *
     * Devuelve un array con:
     * - 'estandar': numero de raciones de la receta original
     * - 'adaptadas': array de versiones adaptadas, cada una con su número de raciones
     *                y los alérgenos que evita
     *
     * @param array $receta  Receta del catálogo
     * @param array $escenario  Escenario con ocupación y grupos de alérgenos
     * @return array
     */
    public function calcularRaciones(array $receta, array $escenario): array
    {
        $totalComensales = $escenario['ocupacion']['total_comensales'];
        $gruposAlergenos = $escenario['ocupacion']['grupos_alergenos'] ?? [];
        $alergenosReceta = $receta['alergenos'] ?? [];

        // Filtrar solo los grupos cuyos alérgenos están presentes en la receta
        // (si la receta no lleva gluten, los celíacos no necesitan versión adaptada)
        $gruposRelevantes = array_filter($gruposAlergenos, function ($grupo) use ($alergenosReceta) {
            $alergenosGrupo = explode('+', $grupo['alergeno']);
            // Si algún alérgeno del grupo está en la receta, el grupo es relevante
            foreach ($alergenosGrupo as $a) {
                if (in_array($a, $alergenosReceta, true)) {
                    return true;
                }
            }
            return false;
        });

        $personasAdaptadas = array_sum(array_column($gruposRelevantes, 'personas'));
        $personasEstandar = $totalComensales - $personasAdaptadas;

        $adaptaciones = [];
        foreach ($gruposRelevantes as $grupo) {
            $alergenosGrupo = explode('+', $grupo['alergeno']);
            // Solo aplicar los alérgenos que están en la receta y que tienen sustitución
            $alergenosAEvitar = array_values(array_filter(
                $alergenosGrupo,
                fn($a) => in_array($a, $alergenosReceta, true)
                    && isset($receta['sustituciones_alergenos'][$a])
            ));

            if (empty($alergenosAEvitar)) {
                // No podemos adaptar: faltan sustituciones para algún alérgeno
                $adaptaciones[] = [
                    'alergenos_evitados' => $alergenosGrupo,
                    'raciones' => $grupo['personas'],
                    'aviso' => 'No hay sustitución disponible para todos los alérgenos',
                    'sin_solucion' => true,
                ];
                continue;
            }

            $adaptaciones[] = [
                'alergenos_evitados' => $alergenosAEvitar,
                'raciones' => $grupo['personas'],
                'sin_solucion' => false,
            ];
        }

        return [
            'estandar' => max(0, $personasEstandar),
            'adaptaciones' => $adaptaciones,
        ];
    }

    /**
     * Aplica las sustituciones de alérgenos a la lista de ingredientes de una receta.
     *
     * Devuelve la lista de ingredientes modificada: cada ingrediente sustituido
     * lleva una marca 'sustituido' = true y la referencia al ingrediente original.
     *
     * @param array $receta
     * @param array $alergenosAEvitar  Lista de alérgenos cuya sustitución aplicar
     * @return array Lista de ingredientes ajustados
     */
    public function aplicarSustituciones(array $receta, array $alergenosAEvitar): array
    {
        $ingredientes = $receta['ingredientes'];
        $sustituciones = $receta['sustituciones_alergenos'] ?? [];

        // Construir mapa de sustituciones aplicables: nombre_original => nuevo ingrediente
        $mapaSustituciones = [];
        foreach ($alergenosAEvitar as $alergeno) {
            if (!isset($sustituciones[$alergeno])) {
                continue;
            }
            foreach ($sustituciones[$alergeno] as $sustitucion) {
                $mapaSustituciones[$sustitucion['ingrediente_original']] = [
                    'nombre' => $sustitucion['sustituto'],
                    'cantidad' => $sustitucion['cantidad'],
                    'unidad' => $sustitucion['unidad'],
                    'original' => $sustitucion['ingrediente_original'],
                    'alergeno_evitado' => $alergeno,
                ];
            }
        }

        // Aplicar
        $resultado = [];
        foreach ($ingredientes as $ing) {
            if (isset($mapaSustituciones[$ing['nombre']])) {
                $sust = $mapaSustituciones[$ing['nombre']];
                $resultado[] = [
                    'nombre' => $sust['nombre'],
                    'cantidad' => $sust['cantidad'],
                    'unidad' => $sust['unidad'],
                    'merma' => $ing['merma'] ?? 0,
                    'alergenos' => [],
                    'sustituido' => true,
                    'ingrediente_original' => $sust['original'],
                ];
            } else {
                // Mantenemos el ingrediente original solo si no contiene alérgenos a evitar
                $contieneAlergeno = !empty(array_intersect($ing['alergenos'] ?? [], $alergenosAEvitar));
                if (!$contieneAlergeno) {
                    $resultado[] = array_merge($ing, ['sustituido' => false]);
                }
                // Si contiene un alérgeno a evitar pero no hay sustitución, se omite
                // (esto idealmente no debería pasar si la receta está bien definida)
            }
        }

        return $resultado;
    }
}
