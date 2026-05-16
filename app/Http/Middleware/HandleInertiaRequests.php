<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * La plantilla raíz Blade que monta la app Vue.
     */
    protected $rootView = 'app';

    /**
     * Datos compartidos en todas las páginas (visibles desde cualquier componente Vue).
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app' => [
                'name' => 'SCP',
                'fullName' => 'Sistema de Cocina de Producción',
                'hotel' => 'Demostración',
            ],
        ]);
    }
}
