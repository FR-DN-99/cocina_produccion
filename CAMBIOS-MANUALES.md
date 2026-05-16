# Cambios a realizar manualmente

Después de copiar los archivos de este paquete sobre el proyecto Laravel recién creado, hay dos cambios manuales que NO se pueden hacer por copia directa porque Laravel ya tiene un archivo en esa ubicación:

## 1. Registrar el middleware de Inertia

Editar `bootstrap/app.php` y añadir el middleware:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // AÑADIR ESTA LÍNEA:
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

## 2. Verificar el archivo `package.json`

Asegúrate de que tiene estas dependencias (el `npm install` del script las instalará, pero por si acaso):

```json
{
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "autoprefixer": "^10.0.0",
    "axios": "^1.6.0",
    "laravel-vite-plugin": "^1.0.0",
    "postcss": "^8.0.0",
    "tailwindcss": "^3.4.0",
    "vite": "^5.0.0"
  },
  "dependencies": {
    "@inertiajs/vue3": "^1.0.0",
    "vue": "^3.4.0"
  }
}
```

## 3. Cache de configuración

Después de cualquier cambio en archivos `.env` o de configuración:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## 4. Storage link (no necesario para esta demo, pero buena práctica)

```bash
php artisan storage:link
```

## Verificación final

Con todo instalado, arranca el proyecto:

```bash
# Terminal 1
npm run dev

# Terminal 2
php artisan serve
```

Abre http://localhost:8000 y deberías ver la pantalla de selección de escenarios.

Si algo falla, los errores más comunes son:
- **Página en blanco**: revisa la consola del navegador y los logs en `storage/logs/laravel.log`
- **Error 500 al cargar**: probablemente falta registrar el middleware (paso 1)
- **Inertia no encuentra páginas**: ejecuta `npm run dev` y refresca el navegador
