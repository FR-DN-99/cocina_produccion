# Actualización SCP - Pestaña Maquinaria

Añade un nuevo botón "Maquinaría" en la topbar que abre un modal con la lista de equipos de cocina.

## Archivos

- `resources/js/Components/CatalogoEquipos.vue` (NUEVO)
- `resources/js/Components/TopBar.vue` (actualizado: nuevo botón)
- `resources/js/Layouts/AppLayout.vue` (actualizado: gestiona el modal)

## Qué muestra

Tabla con:
- ID del equipo
- Nombre y descripción
- Tipo (horno, fogón, etc.)
- Capacidad en raciones por tanda (o "sin límite" si es 999)
- Columna "Usado en": número de recetas que lo emplean + lista con sus nombres

La tabla es ordenable por cualquier columna.

## Cómo aplicarlo

Descomprime sobre el proyecto:

```bash
cd ~/probando\ despliegues/scp-cocina
unzip /ruta/scp-maquinaria.zip
cp -r scp-maquinaria/* .
rm -rf scp-maquinaria
```

Si `npm run dev` está corriendo, recompila automáticamente.

## Cómo probarlo

Botón "Maquinaría" en la esquina superior derecha de la topbar, junto al botón "Catálogo". Abre un modal con la tabla de equipos. Pulsa cualquier cabecera para ordenar (ej: "Capacidad" para ver de más limitante a menos).
