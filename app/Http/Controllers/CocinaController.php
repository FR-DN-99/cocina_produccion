<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraProduccion;
use App\Services\RepositorioDatos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CocinaController extends Controller
{
    public function __construct(
        private RepositorioDatos $repo,
        private CalculadoraProduccion $calculadora,
    ) {}

    /**
     * Pantalla 1: lista de escenarios.
     */
    public function escenarios(): Response
    {
        return Inertia::render('Escenarios', [
            'escenarios' => $this->escenariosConOverrides(),
        ]);
    }

    /**
     * Pantalla 2: escenario + recetas.
     */
    public function recetas(Request $request, string $escenarioId): Response
    {
        $escenario = $this->escenarioConOverride($escenarioId);

        if (!$escenario) {
            abort(404, 'Escenario no encontrado');
        }

        $recetas = $this->repo->recetas();

        $recetasPorCategoria = [];
        foreach ($recetas as $receta) {
            $cat = $receta['categoria'];
            if (!isset($recetasPorCategoria[$cat])) {
                $recetasPorCategoria[$cat] = [];
            }

            $tiempoTotal = array_sum(array_column($receta['elaboraciones'], 'tiempo_minutos'));

            $recetasPorCategoria[$cat][] = [
                'id' => $receta['id'],
                'nombre' => $receta['nombre'],
                'categoria' => $receta['categoria'],
                'alergenos' => $receta['alergenos'],
                'numero_elaboraciones' => count($receta['elaboraciones']),
                'tiempo_total_minutos' => $tiempoTotal,
            ];
        }

        return Inertia::render('Recetas', [
            'escenario' => $escenario,
            'recetasPorCategoria' => $recetasPorCategoria,
        ]);
    }

    /**
     * Pantalla 3: resultado del cálculo.
     */
    public function calcular(Request $request, string $escenarioId): Response|RedirectResponse
    {
        $recetas = $request->input('recetas', []);

        if (is_string($recetas)) {
            $recetas = array_filter(explode(',', $recetas));
        }

        if (empty($recetas)) {
            return redirect()->route('recetas', ['escenarioId' => $escenarioId]);
        }

        // Usar el escenario con override (si existe) para los cálculos
        $escenarioConOverride = $this->escenarioConOverride($escenarioId);
        $resultado = $this->calculadora->calcular($escenarioId, $recetas, $escenarioConOverride);

        if (isset($resultado['error'])) {
            abort(422, $resultado['error']);
        }

        return Inertia::render('Resultado', [
            'resultado' => $resultado,
        ]);
    }

    /**
     * API: devuelve el catálogo completo con ficha técnica de cada receta.
     */
    public function catalogoRecetas(): JsonResponse
    {
        return response()->json([
            'recetas' => $this->repo->recetas(),
            'equipos' => $this->repo->equipos(),
        ]);
    }

    /**
     * Guardar modificación temporal de un escenario en sesión.
     */
    public function modificarEscenario(Request $request, string $escenarioId): RedirectResponse
    {
        $validated = $request->validate([
            'total_comensales' => 'required|integer|min:1|max:9999',
            'pension_completa' => 'nullable|integer|min:0',
            'media_pension' => 'nullable|integer|min:0',
            'hora_servicio' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'grupos_alergenos' => 'nullable|array',
            'grupos_alergenos.*.alergeno' => 'required|string',
            'grupos_alergenos.*.personas' => 'required|integer|min:1',
        ]);

        $overrides = session('escenario_overrides', []);
        $overrides[$escenarioId] = $validated;
        session(['escenario_overrides' => $overrides]);

        return redirect()->route('recetas', ['escenarioId' => $escenarioId]);
    }

    /**
     * Restaurar un escenario a sus valores originales (quitar override de sesión).
     */
    public function restaurarEscenario(string $escenarioId): RedirectResponse
    {
        $overrides = session('escenario_overrides', []);
        unset($overrides[$escenarioId]);
        session(['escenario_overrides' => $overrides]);

        return redirect()->route('recetas', ['escenarioId' => $escenarioId]);
    }

    /**
     * Devuelve la lista de escenarios aplicando los overrides de sesión.
     */
    private function escenariosConOverrides(): array
    {
        $escenarios = $this->repo->escenarios();
        $overrides = session('escenario_overrides', []);

        return array_map(function ($esc) use ($overrides) {
            if (isset($overrides[$esc['id']])) {
                $esc = $this->aplicarOverride($esc, $overrides[$esc['id']]);
                $esc['modificado'] = true;
            } else {
                $esc['modificado'] = false;
            }
            return $esc;
        }, $escenarios);
    }

    /**
     * Devuelve un escenario concreto aplicando el override si existe.
     */
    private function escenarioConOverride(string $id): ?array
    {
        $escenario = $this->repo->escenarioPorId($id);
        if (!$escenario) {
            return null;
        }
        $overrides = session('escenario_overrides', []);
        if (isset($overrides[$id])) {
            $escenario = $this->aplicarOverride($escenario, $overrides[$id]);
            $escenario['modificado'] = true;
        } else {
            $escenario['modificado'] = false;
        }
        return $escenario;
    }

    /**
     * Aplica los valores del override sobre el escenario original.
     */
    private function aplicarOverride(array $escenario, array $override): array
    {
        $escenario['hora_servicio'] = $override['hora_servicio'];
        $escenario['ocupacion']['total_comensales'] = $override['total_comensales'];
        $escenario['ocupacion']['pension_completa'] = $override['pension_completa'] ?? 0;
        $escenario['ocupacion']['media_pension'] = $override['media_pension'] ?? 0;
        $escenario['ocupacion']['grupos_alergenos'] = $override['grupos_alergenos'] ?? [];
        return $escenario;
    }
}
