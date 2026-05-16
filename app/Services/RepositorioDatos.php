<?php

namespace App\Services;

/**
 * RepositorioDatos
 *
 * Responsable de cargar los datos del sistema (recetas, equipos, escenarios).
 * En esta versión demo lee de ficheros JSON locales.
 *
 * En producción, esta clase será sustituida por una que consuma:
 * - Las recetas de la base de datos del SCP
 * - Los equipos de configuración
 * - Los escenarios de la API de Noray
 */
class RepositorioDatos
{
    public function recetas(): array
    {
        return $this->cargarJson('recetas.json')['recetas'] ?? [];
    }

    public function recetaPorId(string $id): ?array
    {
        foreach ($this->recetas() as $receta) {
            if ($receta['id'] === $id) {
                return $receta;
            }
        }
        return null;
    }

    public function recetasPorIds(array $ids): array
    {
        return array_values(array_filter(
            $this->recetas(),
            fn($receta) => in_array($receta['id'], $ids, true)
        ));
    }

    public function equipos(): array
    {
        return $this->cargarJson('equipos.json')['equipos'] ?? [];
    }

    public function equipoPorTipo(string $tipo): ?array
    {
        foreach ($this->equipos() as $equipo) {
            if ($equipo['id'] === $tipo) {
                return $equipo;
            }
        }
        return null;
    }

    public function escenarios(): array
    {
        return $this->cargarJson('escenarios.json')['escenarios'] ?? [];
    }

    public function escenarioPorId(string $id): ?array
    {
        foreach ($this->escenarios() as $escenario) {
            if ($escenario['id'] === $id) {
                return $escenario;
            }
        }
        return null;
    }

    private function cargarJson(string $fichero): array
    {
        $ruta = storage_path("app/datos/{$fichero}");

        if (!file_exists($ruta)) {
            return [];
        }

        $contenido = file_get_contents($ruta);
        return json_decode($contenido, true) ?? [];
    }
}