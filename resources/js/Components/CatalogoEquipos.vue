<script setup>
import { ref, computed, watch } from 'vue';
import Modal from './Modal.vue';
import SortableHeader from './SortableHeader.vue';
import { useSortable } from '@/composables/useSortable.js';

const props = defineProps({
    visible: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const cargando = ref(false);
const recetas = ref([]);
const equipos = ref([]);
const error = ref(null);

watch(() => props.visible, async (v) => {
    if (v && equipos.value.length === 0) {
        await cargarCatalogo();
    }
});

async function cargarCatalogo() {
    cargando.value = true;
    error.value = null;
    try {
        const res = await fetch('/api/catalogo', {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('Error al cargar catálogo');
        const data = await res.json();
        recetas.value = data.recetas || [];
        equipos.value = data.equipos || [];
    } catch (e) {
        error.value = e.message;
    } finally {
        cargando.value = false;
    }
}

// Calcular qué recetas usan cada equipo
const equiposEnriquecidos = computed(() => {
    return equipos.value.map(eq => {
        const recetasQueLoUsan = recetas.value.filter(r =>
            r.elaboraciones.some(e => e.equipo === eq.id)
        );
        const tieneLimitacion = eq.capacidad_raciones < 999;
        return {
            ...eq,
            recetas_que_lo_usan: recetasQueLoUsan,
            numero_recetas: recetasQueLoUsan.length,
            tiene_limitacion: tieneLimitacion,
            capacidad_legible: tieneLimitacion ? eq.capacidad_raciones : null,
        };
    });
});

const { sortedItems, sortBy, sortKey, sortDir } = useSortable(equiposEnriquecidos);

function tipoLegible(tipo) {
    const map = {
        horno: 'Horno',
        abatidor: 'Abatidor',
        fogon: 'Fogón',
        plancha: 'Plancha',
        encimera: 'Mesa de trabajo',
        tritura: 'Trituradora',
        nevera: 'Nevera',
        congelador: 'Congelador',
    };
    return map[tipo] || tipo.charAt(0).toUpperCase() + tipo.slice(1);
}

function nombresRecetas(recetasUso) {
    if (recetasUso.length === 0) return 'No se usa en ninguna receta';
    return recetasUso.map(r => r.nombre).join(', ');
}
</script>

<template>
    <Modal
        :visible="visible"
        size="xl"
        title="Maquinaria de cocina"
        subtitle="Equipos disponibles y su uso en las recetas del catálogo"
        @close="$emit('close')"
    >
        <div v-if="cargando" class="px-6 py-10 text-center text-ink-soft">
            Cargando equipos...
        </div>

        <div v-else-if="error" class="px-6 py-10 text-center text-danger">
            {{ error }}
        </div>

        <div v-else class="p-6">
            <div class="px-4 py-2.5 bg-bg-soft border border-line border-b-0 rounded-t-sm text-[11px] text-ink-soft flex items-center gap-2">
                <span class="w-4 h-4 bg-warn text-white rounded-full inline-flex items-center justify-center font-mono text-[10px] font-semibold flex-shrink-0">i</span>
                <span>La capacidad indica cuántas raciones admite el equipo en una sola tanda. Cuando una elaboración supera esa cifra, el sistema planifica varias tandas automáticamente.</span>
            </div>

            <table class="w-full text-[13px] border border-line rounded-b-sm overflow-hidden">
                <thead>
                    <tr class="bg-bg-soft">
                        <SortableHeader sort-key="id" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">ID</SortableHeader>
                        <SortableHeader sort-key="nombre" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Nombre</SortableHeader>
                        <SortableHeader sort-key="tipo" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Tipo</SortableHeader>
                        <SortableHeader sort-key="capacidad_raciones" align="right" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Capacidad</SortableHeader>
                        <SortableHeader sort-key="numero_recetas" align="right" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Usado en</SortableHeader>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="eq in sortedItems"
                        :key="eq.id"
                        class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-ink-mute font-medium w-24">{{ eq.id }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium">{{ eq.nombre }}</div>
                            <div v-if="eq.descripcion" class="text-xs text-ink-soft mt-0.5">{{ eq.descripcion }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-soft w-32">{{ tipoLegible(eq.tipo) }}</td>
                        <td class="px-4 py-3 text-right w-32">
                            <div v-if="eq.tiene_limitacion">
                                <div class="font-mono text-base font-medium">{{ eq.capacidad_legible }}</div>
                                <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-px">raciones/tanda</div>
                            </div>
                            <div v-else class="text-xs text-ink-mute italic">Sin límite</div>
                        </td>
                        <td class="px-4 py-3 w-80">
                            <div v-if="eq.numero_recetas === 0" class="text-xs text-ink-mute italic">
                                No se usa en ninguna receta
                            </div>
                            <div v-else>
                                <div class="font-mono text-sm font-medium">{{ eq.numero_recetas }}</div>
                                <div class="text-[11px] text-ink-soft mt-0.5">{{ nombresRecetas(eq.recetas_que_lo_usan) }}</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-4 text-[11px] text-ink-mute italic">
                {{ equipos.length }} {{ equipos.length === 1 ? 'equipo registrado' : 'equipos registrados' }} en el sistema.
            </div>
        </div>
    </Modal>
</template>
