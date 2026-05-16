# Actualización SCP - Mejoras V2

Esta actualización añade cuatro mejoras al SCP:

1. **Tablas ordenables** en escenarios, mise en place, ingredientes y planificación temporal. Clic en cabecera ordena ascendente, segundo clic descendente, tercer clic vuelve al orden original.
2. **Catálogo de recetas** accesible desde la topbar. Modal con listado y ficha técnica completa de cada receta.
3. **Checkboxes en planificación temporal** para marcar tareas completadas. Al marcar, la fila se atenúa y aparece línea de tachado.
4. **Edición de escenarios** desde la pantalla de escenarios. Permite modificar comensales, alérgenos y hora de servicio. Los cambios solo se mantienen en sesión.

---

## Archivos a copiar

Sobrescribe estos archivos en tu proyecto. Conserva las rutas exactas:

### Backend
- `app/Http/Controllers/CocinaController.php`
- `app/Services/CalculadoraProduccion.php`
- `routes/web.php`

### Frontend - composables (carpeta NUEVA)
Crea la carpeta `resources/js/composables/` y dentro:
- `useSortable.js`

### Frontend - componentes
- `resources/js/Components/TopBar.vue` (modificado)
- `resources/js/Components/SortableHeader.vue` (NUEVO)
- `resources/js/Components/Modal.vue` (NUEVO)
- `resources/js/Components/CatalogoRecetas.vue` (NUEVO)
- `resources/js/Components/EditorEscenario.vue` (NUEVO)
- `resources/js/Components/IngredientesTabla.vue` (NUEVO)

### Frontend - layout
- `resources/js/Layouts/AppLayout.vue` (modificado)

### Frontend - páginas
- `resources/js/Pages/Escenarios.vue` (modificado)
- `resources/js/Pages/Resultado.vue` (modificado)

---

## Después de copiar

Si `npm run dev` está corriendo, recompilará automáticamente al guardar. Si no:

```bash
npm run dev
```

No hace falta tocar nada de Laravel ni reiniciar el servidor.

---

## Cómo probar cada mejora

### Tablas ordenables
- En la lista de escenarios: clic en "Comensales" para ordenar por número de comensales.
- En el resultado de un cálculo: clic en "Ingrediente" del mise en place para ordenar alfabéticamente.
- En la planificación: clic en "Duración" para ver las tareas más largas primero.

### Catálogo de recetas
- Botón "Catálogo" en la topbar (esquina superior derecha).
- Se abre un modal con todas las recetas agrupadas por categoría.
- Clic en una receta abre un segundo modal con ficha técnica completa: ingredientes por persona, alérgenos por ingrediente, proceso de elaboración con equipo y tiempo, sustituciones por alérgeno.

### Checkboxes en planificación
- En la pantalla de resultado, columna de la izquierda de la tabla de planificación.
- Marca una tarea: la fila se atenúa y aparece línea de tachado.
- El estado se mantiene mientras no recargues la página. Es manual de momento.

### Edición de escenarios
- En la lista de escenarios, botón "Editar" a la derecha de cada fila (no entra al escenario, abre el modal).
- Modifica comensales, hora de servicio, pensión, o añade/elimina grupos de alérgenos.
- Al aplicar, vuelve a la pantalla de selección de recetas con los nuevos valores.
- Los escenarios modificados se marcan con badge "Mod." junto al ID.
- El botón "Restaurar original" devuelve a los valores del JSON.
- Importante: los cambios solo duran la sesión. Al cerrar el navegador se restauran.

---

## Estructura final del proyecto

```
scp-cocina/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── CocinaController.php           ← actualizado
│   └── Services/
│       └── CalculadoraProduccion.php          ← actualizado
├── resources/
│   └── js/
│       ├── Components/
│       │   ├── AlertBox.vue
│       │   ├── Badge.vue
│       │   ├── CatalogoRecetas.vue            ← nuevo
│       │   ├── EditorEscenario.vue            ← nuevo
│       │   ├── IngredientesTabla.vue          ← nuevo
│       │   ├── Modal.vue                      ← nuevo
│       │   ├── SortableHeader.vue             ← nuevo
│       │   └── TopBar.vue                     ← actualizado
│       ├── composables/                       ← carpeta nueva
│       │   └── useSortable.js                 ← nuevo
│       ├── Layouts/
│       │   └── AppLayout.vue                  ← actualizado
│       └── Pages/
│           ├── Escenarios.vue                 ← actualizado
│           ├── Recetas.vue                    ← sin cambios
│           └── Resultado.vue                  ← actualizado
└── routes/
    └── web.php                                ← actualizado
```
