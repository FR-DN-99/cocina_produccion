# Arquitectura del motor de cálculo

Este documento explica cómo funciona internamente el motor del SCP. Útil para mantener el código en el futuro o explicárselo a un programador que se incorpore.

## Visión general

El sistema se divide en cuatro capas:

1. **Datos** (`storage/app/datos/*.json`): origen de los datos. En producción será reemplazado por base de datos + Noray API.
2. **Repositorio** (`RepositorioDatos`): única clase que sabe leer los datos. Si cambia el origen, solo se cambia esta clase.
3. **Servicios** (`GestorAlergenos`, `PlanificadorTemporal`): lógica de negocio especializada.
4. **Orquestador** (`CalculadoraProduccion`): combina los servicios para producir el resultado final.

## Flujo de cálculo

Cuando se llama a `CalculadoraProduccion::calcular($escenarioId, $recetasIds)`:

1. Se carga el escenario y las recetas seleccionadas desde el repositorio.
2. Para cada receta:
   - `GestorAlergenos::calcularRaciones()` decide cuántas raciones de la versión estándar y cuántas adaptadas se necesitan.
   - `GestorAlergenos::aplicarSustituciones()` calcula la lista de ingredientes adaptada para cada grupo.
   - Se escalan los ingredientes (estándar y adaptados) al número de raciones, aplicando merma.
3. `PlanificadorTemporal::planificar()` calcula:
   - Si cada elaboración necesita varias tandas según la capacidad del equipo.
   - La hora de inicio de cada tarea, trabajando hacia atrás desde la hora del servicio.
4. Se generan avisos relevantes: contaminación cruzada, tandas múltiples, falta de sustituciones, elaboraciones compartidas.
5. Se devuelve el resultado completo.

## Cálculo de cantidades y merma

La merma indica el porcentaje de un ingrediente que se pierde durante la manipulación o cocción. Por tanto, para tener X g útiles hay que comprar más:

```
cantidad_comprar = cantidad_útil / (1 - merma)
```

Por ejemplo, 200 g de calabaza limpia con merma 0.15:

```
200 / (1 - 0.15) = 235 g de calabaza bruta
```

## Cálculo de tandas

Para cada elaboración con limitación de capacidad (típicamente el horno):

```
tandas = ceil(raciones_totales / capacidad_por_tanda)
```

Cuando hay tandas múltiples, la duración efectiva de la elaboración se multiplica por el número de tandas, ya que se hacen secuencialmente.

## Planificación temporal hacia atrás

Para cada receta:

1. Se suman las duraciones de todas sus elaboraciones (multiplicando por tandas en horno).
2. La hora de inicio de la receta es: `hora_servicio - duración_total`.
3. Las elaboraciones se ordenan en secuencia, cada una empieza al terminar la anterior.

Esta lógica es deliberadamente simple: asume que las elaboraciones de una receta van en orden secuencial. Para mejoras futuras (paralelización de tareas en distintos equipos), habría que modelar dependencias entre elaboraciones.

## Gestión de alérgenos

El sistema distingue dos tipos de alérgenos:

- **Simples**: gluten, lactosa, huevo, frutos secos, etc.
- **Combinados**: representados con `+` (ej: `gluten+lactosa`). Significa que un grupo de personas tiene varias intolerancias a la vez.

Al detectar un alérgeno en un grupo:

1. Se mira si la receta contiene ese alérgeno.
2. Si no lo contiene, no se necesita versión adaptada (el grupo come la receta normal).
3. Si lo contiene, se busca la sustitución correspondiente en `sustituciones_alergenos` de la receta.
4. Si no hay sustitución definida, se marca como "sin solución" para alertar al cocinero.

## Detección de elaboraciones compartidas

Las recetas pueden marcar elaboraciones como `compartible_con: ["REC-XXX"]`. Cuando dos recetas seleccionadas comparten una elaboración (mismo descripcion y compartible_con), el sistema lo detecta y lo señala como aviso.

En esta versión solo se genera el aviso. La siguiente iteración debería unificar realmente las cantidades y duraciones en la planificación.

## Cómo añadir una nueva receta

1. Editar `storage/app/datos/recetas.json`.
2. Añadir un nuevo objeto con la estructura de las recetas existentes.
3. Asegurarse de incluir todas las sustituciones para los alérgenos relevantes.
4. La receta aparece automáticamente en el catálogo, no hace falta tocar código.

## Cómo añadir un nuevo escenario

Editar `storage/app/datos/escenarios.json` siguiendo la misma estructura.

## Cómo cambiar la capacidad de un equipo

Editar `storage/app/datos/equipos.json` y modificar `capacidad_raciones` del equipo correspondiente. Los cálculos de tandas usan este valor automáticamente.

## Cuando se conecte a Noray

Solo hay que sustituir el método `escenarios()` y `escenarioPorId()` de `RepositorioDatos` para que en lugar de leer del JSON, hagan una llamada a Noray API. El resto del motor no se entera.

```php
// Versión actual (demo)
public function escenarioPorId(string $id): ?array
{
    return $this->cargarJson('escenarios.json')['escenarios'] ?? [];
}

// Versión futura (producción)
public function escenarioActual(): array
{
    return $this->norayClient->obtenerOcupacionActual();
}
```
