<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import SortableHeader from '@/Components/SortableHeader.vue';
import EditorEscenario from '@/Components/EditorEscenario.vue';
import { useSortable } from '@/composables/useSortable.js';

const props = defineProps({
    escenarios: { type: Array, required: true },
});

const fechaHoy = new Date().toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const editorVisible = ref(false);
const escenarioEditando = ref(null);

function editarEscenario(e, esc) {
    e.stopPropagation();
    escenarioEditando.value = esc;
    editorVisible.value = true;
}

function cerrarEditor() {
    editorVisible.value = false;
    escenarioEditando.value = null;
}

const escenariosConCalculos = props.escenarios.map(e => ({
    ...e,
    _comensales: e.ocupacion.total_comensales,
    _alergicos: (e.ocupacion.grupos_alergenos || []).reduce((acc, g) => acc + g.personas, 0),
}));

const { sortedItems, sortBy, sortKey, sortDir } = useSortable(escenariosConCalculos);

function variantePorTipo(tipo) {
    return {
        estandar: 'normal',
        alerta: 'alerta',
        evento: 'evento',
        menu_doble: 'warn',
    }[tipo] || 'normal';
}

function etiquetaPorTipo(esc) {
    const grupos = esc.ocupacion.grupos_alergenos || [];
    if (esc.tipo === 'evento') return 'Evento';
    if (esc.tipo === 'menu_doble') return 'Menú doble';
    if (esc.tipo === 'estandar') return 'Estándar';
    if (grupos.length === 1) return '1 alérgeno';
    if (grupos.length > 1) return `${grupos.length} alérgenos`;
    return 'Estándar';
}

function descripcionAlergicos(esc) {
    const grupos = esc.ocupacion.grupos_alergenos || [];
    if (grupos.length === 0) return 'sin alérgenos';
    if (grupos.length === 1) return etiquetaGrupo(grupos[0]);
    return `en ${grupos.length} grupos`;
}

function etiquetaGrupo(grupo) {
    const map = {
        gluten: 'celíacos',
        lactosa: 'lactosa',
        huevo: 'huevo',
        'gluten+lactosa': 'gluten+lactosa',
    };
    return map[grupo.alergeno] || grupo.alergeno;
}

function entrar(escenarioId) {
    router.get(`/escenarios/${escenarioId}/recetas`);
}
</script>

<template>
    <AppLayout
        :breadcrumb="[{ label: 'Escenarios' }]"
        :topbar-right="{ Fecha: fechaHoy, Usuario: 'cocina-01' }"
    >
        <div class="bg-bg-panel border-b border-line px-4 md:px-8 py-4">
            <h1 class="text-lg font-semibold mb-0.5">Selección de escenario</h1>
            <p class="text-[13px] text-ink-soft">Datos que el sistema recibe del PMS para el servicio a planificar</p>
        </div>

        <div class="bg-warn-soft border-b border-[#ebd0a0] px-4 md:px-8 py-2.5 text-xs text-[#8a4708] flex items-start gap-2.5">
            <span class="w-4 h-4 bg-warn text-white rounded-full inline-flex items-center justify-center font-mono text-[11px] font-semibold flex-shrink-0 mt-0.5">i</span>
            <span><strong class="font-semibold">Modo demostración.</strong> En producción esta pantalla no aparece, el sistema entra directamente al servicio del día actual leído de Noray. Puedes editar cualquier escenario con el botón de la derecha.</span>
        </div>

        <div class="px-4 md:px-8 py-6">
            <div class="bg-bg-panel border border-line rounded-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead>
                            <tr class="bg-bg-soft">
                                <SortableHeader sort-key="id" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">ID</SortableHeader>
                                <SortableHeader sort-key="titulo" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Escenario</SortableHeader>
                                <SortableHeader sort-key="_comensales" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Comensales</SortableHeader>
                                <SortableHeader sort-key="_alergicos" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Alérgicos</SortableHeader>
                                <SortableHeader sort-key="hora_servicio" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Hora</SortableHeader>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Tipo</th>
                                <th class="text-right text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line w-24">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="esc in sortedItems"
                                :key="esc.id"
                                @click="entrar(esc.id)"
                                class="border-b border-line-soft last:border-b-0 cursor-pointer hover:bg-bg-soft transition-colors"
                                :class="esc.modificado ? 'bg-warn-soft/30' : ''"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-ink-mute font-medium w-20">
                                    {{ esc.id }}
                                    <Badge v-if="esc.modificado" variant="warn" class="ml-1">Mod.</Badge>
                                </td>
                                <td class="px-4 py-3 min-w-[200px]">
                                    <div class="text-sm font-semibold">{{ esc.titulo }}</div>
                                    <div class="text-xs text-ink-soft mt-0.5">{{ esc.descripcion }}</div>
                                </td>
                                <td class="px-4 py-3 w-32">
                                    <div class="font-mono text-base font-medium">{{ esc.ocupacion.total_comensales }}</div>
                                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-px">comensales</div>
                                </td>
                                <td class="px-4 py-3 w-32">
                                    <div class="font-mono text-base font-medium" :class="esc._alergicos === 0 ? 'text-ink-mute' : ''">
                                        {{ esc._alergicos }}
                                    </div>
                                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-px">{{ descripcionAlergicos(esc) }}</div>
                                </td>
                                <td class="px-4 py-3 w-24">
                                    <div class="font-mono text-base font-medium">{{ esc.hora_servicio }}</div>
                                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-px">{{ esc.tipo_servicio }}</div>
                                </td>
                                <td class="px-4 py-3 w-28">
                                    <Badge :variant="variantePorTipo(esc.tipo)">{{ etiquetaPorTipo(esc) }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-right w-24">
                                    <button
                                        @click="editarEscenario($event, esc)"
                                        class="text-xs text-accent hover:text-[#174d8a] font-medium px-2 py-1 hover:bg-accent-soft rounded-sm"
                                    >Editar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <EditorEscenario
            :visible="editorVisible"
            :escenario="escenarioEditando"
            @close="cerrarEditor"
        />
    </AppLayout>
</template>
