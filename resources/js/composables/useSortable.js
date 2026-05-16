import { ref, computed } from 'vue';

/**
 * Composable para ordenar tablas.
 *
 * Uso:
 *   const { sortedItems, sortBy, sortKey, sortDir } = useSortable(items, defaultKey);
 *
 *   En la cabecera de la columna:
 *     <th @click="sortBy('nombre')">
 *       Nombre
 *       <span v-if="sortKey === 'nombre'">{{ sortDir === 'asc' ? '↑' : sortDir === 'desc' ? '↓' : '' }}</span>
 *     </th>
 *
 *   En el cuerpo:
 *     <tr v-for="item in sortedItems">...</tr>
 *
 * Tres estados al pulsar una cabecera:
 *   1. asc → 2. desc → 3. sin orden (orden original)
 *
 * @param {Ref|Array} source  array o ref que contiene los elementos
 * @param {Function} accessor opcional, función que recibe un item y la key y devuelve el valor a comparar
 */
export function useSortable(source, accessor = null) {
    const sortKey = ref(null);
    const sortDir = ref(null);

    function getValue(item, key) {
        if (accessor) return accessor(item, key);
        // Soporta notación con puntos: 'ocupacion.total_comensales'
        return key.split('.').reduce((obj, k) => (obj ? obj[k] : undefined), item);
    }

    function sortBy(key) {
        if (sortKey.value !== key) {
            sortKey.value = key;
            sortDir.value = 'asc';
        } else if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else {
            sortKey.value = null;
            sortDir.value = null;
        }
    }

    const sortedItems = computed(() => {
        const items = Array.isArray(source) ? source : source.value;
        if (!sortKey.value || !sortDir.value) {
            return items;
        }
        const factor = sortDir.value === 'asc' ? 1 : -1;
        return [...items].sort((a, b) => {
            const va = getValue(a, sortKey.value);
            const vb = getValue(b, sortKey.value);
            if (va == null && vb == null) return 0;
            if (va == null) return 1 * factor;
            if (vb == null) return -1 * factor;
            // Numérico
            if (typeof va === 'number' && typeof vb === 'number') {
                return (va - vb) * factor;
            }
            // Cadenas que parezcan números (cantidades formateadas tipo "5,28")
            const na = parseFloat(String(va).replace(',', '.').replace(/[^\d.-]/g, ''));
            const nb = parseFloat(String(vb).replace(',', '.').replace(/[^\d.-]/g, ''));
            if (!isNaN(na) && !isNaN(nb) && String(va).match(/^[\d.,\s]+$/) && String(vb).match(/^[\d.,\s]+$/)) {
                return (na - nb) * factor;
            }
            return String(va).localeCompare(String(vb), 'es') * factor;
        });
    });

    return { sortedItems, sortBy, sortKey, sortDir };
}
