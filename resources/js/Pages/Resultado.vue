<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import AlertBox from '@/Components/AlertBox.vue';
import SortableHeader from '@/Components/SortableHeader.vue';
import IngredientesTabla from '@/Components/IngredientesTabla.vue';
import { useSortable } from '@/composables/useSortable.js';

const props = defineProps({
    resultado: { type: Object, required: true },
});

const escenarioId = computed(() => props.resultado.escenario.id);
const titulo = computed(() => props.resultado.escenario.titulo);
const resumen = computed(() => props.resultado.resumen);
const miseEnPlace = computed(() => props.resultado.mise_en_place || []);
const todosAvisos = computed(() => props.resultado.avisos || []);

const avisosPorTarea = computed(() => {
    const mapa = {};
    for (const a of todosAvisos.value) {
        if (a.alcance === 'tarea' && a.referencia_id) {
            if (!mapa[a.referencia_id]) mapa[a.referencia_id] = [];
            mapa[a.referencia_id].push(a);
        }
    }
    return mapa;
});

const avisosPorReceta = computed(() => {
    const mapa = {};
    for (const a of todosAvisos.value) {
        if (a.alcance === 'receta' && a.referencia_id) {
            if (!mapa[a.referencia_id]) mapa[a.referencia_id] = [];
            mapa[a.referencia_id].push(a);
        }
    }
    return mapa;
});

const conteoAvisosPorTipo = computed(() => {
    const c = { danger: 0, warn: 0, ok: 0 };
    for (const a of todosAvisos.value) c[a.tipo]++;
    return c;
});

function tipoAvisoMasGrave(refId, mapa) {
    const lista = mapa[refId];
    if (!lista || lista.length === 0) return null;
    if (lista.some(a => a.tipo === 'danger')) return 'danger';
    if (lista.some(a => a.tipo === 'warn')) return 'warn';
    return 'ok';
}

const { sortedItems: mepSorted, sortBy: mepSortBy, sortKey: mepSortKey, sortDir: mepSortDir } = useSortable(miseEnPlace);

const planificacion = computed(() => props.resultado.planificacion || []);
const { sortedItems: planSorted, sortBy: planSortBy, sortKey: planSortKey, sortDir: planSortDir } = useSortable(planificacion);

const tareasCompletadas = ref(new Set());

function tareaKey(tarea, idx) {
    return tarea.elaboracion_id || `tarea-${idx}`;
}

function toggleTarea(key) {
    if (tareasCompletadas.value.has(key)) {
        tareasCompletadas.value.delete(key);
    } else {
        tareasCompletadas.value.add(key);
    }
    tareasCompletadas.value = new Set(tareasCompletadas.value);
}

function duracionTotal() {
    const inicio = resumen.value.hora_inicio;
    const fin = resumen.value.hora_servicio;
    if (!inicio || !fin) return '—';
    const [h1, m1] = inicio.split(':').map(Number);
    const [h2, m2] = fin.split(':').map(Number);
    const total = (h2 * 60 + m2) - (h1 * 60 + m1);
    const h = Math.floor(total / 60);
    const m = total % 60;
    if (h === 0) return `${m} min`;
    if (m === 0) return `${h} h`;
    return `${h} h ${m}`;
}

function resumenUso(usadoEn) {
    if (!usadoEn || usadoEn.length === 0) return '';
    const recetasUnicas = [...new Set(usadoEn.map(u => u.receta_nombre))];
    if (recetasUnicas.length === 1) {
        if (usadoEn.length > 1) {
            const versiones = [...new Set(usadoEn.map(u => u.version))];
            return `${recetasUnicas[0]} (${versiones.join(', ')})`;
        }
        return recetasUnicas[0];
    }
    return recetasUnicas.join(', ');
}

function irAAviso(aviso) {
    const id = aviso.id;
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
        elemento.classList.add('ring-2', 'ring-accent');
        setTimeout(() => elemento.classList.remove('ring-2', 'ring-accent'), 1500);
    }
}

function bordeFila(tipo) {
    if (tipo === 'danger') return 'border-l-[3px] border-l-danger';
    if (tipo === 'warn') return 'border-l-[3px] border-l-warn';
    if (tipo === 'ok') return 'border-l-[3px] border-l-ok';
    return '';
}

const iconoAviso = {
    danger: '⚠',
    warn: '!',
    ok: 'ⓘ',
};
</script>

<template>
    <AppLayout
        :breadcrumb="[
            { label: 'Escenarios', href: '/' },
            { label: escenarioId, href: `/escenarios/${escenarioId}/recetas` },
            { label: 'Resultado de producción' }
        ]"
    >
        <Link
            :href="`/escenarios/${escenarioId}/recetas`"
            class="inline-flex items-center gap-1.5 text-xs text-ink-soft px-4 md:px-8 py-3 bg-bg-panel border-b border-line hover:text-accent transition-colors"
        >
            ← Ajustar selección
        </Link>

        <!-- RESUMEN -->
        <div class="bg-bg-panel border-b border-line px-4 md:px-8 py-4 md:py-5 flex flex-col lg:grid lg:items-center gap-4 lg:gap-10" style="grid-template-columns: 1fr auto">
            <div>
                <h2 class="text-lg md:text-xl font-semibold mb-1">{{ titulo }}</h2>
                <p class="text-[13px] text-ink-soft">
                    {{ resumen.numero_recetas }} {{ resumen.numero_recetas === 1 ? 'receta' : 'recetas' }} · {{ resumen.total_comensales }} comensales · servicio {{ resumen.hora_servicio }}
                </p>
                <div v-if="todosAvisos.length > 0" class="mt-3 flex flex-wrap gap-3 text-xs">
                    <a
                        v-if="conteoAvisosPorTipo.danger > 0"
                        href="#resumen-avisos"
                        @click.prevent="document.getElementById('resumen-avisos')?.scrollIntoView({ behavior: 'smooth' })"
                        class="flex items-center gap-1.5 text-danger hover:underline cursor-pointer"
                    >
                        <span class="font-mono font-bold">{{ conteoAvisosPorTipo.danger }}</span>
                        <span>{{ conteoAvisosPorTipo.danger === 1 ? 'crítico' : 'críticos' }}</span>
                    </a>
                    <a
                        v-if="conteoAvisosPorTipo.warn > 0"
                        href="#resumen-avisos"
                        @click.prevent="document.getElementById('resumen-avisos')?.scrollIntoView({ behavior: 'smooth' })"
                        class="flex items-center gap-1.5 text-warn hover:underline cursor-pointer"
                    >
                        <span class="font-mono font-bold">{{ conteoAvisosPorTipo.warn }}</span>
                        <span>{{ conteoAvisosPorTipo.warn === 1 ? 'aviso' : 'avisos' }}</span>
                    </a>
                    <a
                        v-if="conteoAvisosPorTipo.ok > 0"
                        href="#resumen-avisos"
                        @click.prevent="document.getElementById('resumen-avisos')?.scrollIntoView({ behavior: 'smooth' })"
                        class="flex items-center gap-1.5 text-ok hover:underline cursor-pointer"
                    >
                        <span class="font-mono font-bold">{{ conteoAvisosPorTipo.ok }}</span>
                        <span>info</span>
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:flex gap-4 sm:gap-6 lg:gap-8 items-center">
                <div class="text-left lg:text-right">
                    <div class="font-mono text-xl md:text-2xl font-medium leading-none">{{ resumen.raciones_estandar }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-1 font-semibold">Estándar</div>
                </div>
                <div v-if="resumen.raciones_adaptadas > 0" class="text-left lg:text-right">
                    <div class="font-mono text-xl md:text-2xl font-medium leading-none">{{ resumen.raciones_adaptadas }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-1 font-semibold">Adaptadas</div>
                </div>
                <div class="text-left lg:text-right">
                    <div class="font-mono text-xl md:text-2xl font-medium leading-none">{{ resumen.hora_inicio || '—' }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-1 font-semibold">Inicio</div>
                </div>
                <div class="text-left lg:text-right">
                    <div class="font-mono text-xl md:text-2xl font-medium leading-none">{{ duracionTotal() }}</div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-1 font-semibold">Duración</div>
                </div>
            </div>
        </div>

        <div class="px-4 md:px-8 py-6">

            <!-- MISE EN PLACE -->
            <div v-if="miseEnPlace.length > 0" class="bg-bg-panel border border-line rounded-sm mb-4 overflow-hidden">
                <div class="px-4 md:px-5 py-3 md:py-3.5 bg-bg-header text-white flex justify-between items-center gap-3">
                    <h3 class="text-[15px] font-semibold flex items-center gap-2.5 min-w-0">
                        <span class="truncate">Mise en place</span>
                        <span class="hidden sm:inline font-mono text-[11px] text-[#9aa3b0] font-normal bg-white/10 px-1.5 py-0.5 rounded-sm flex-shrink-0">total agregado</span>
                    </h3>
                    <div class="font-mono text-xs text-[#9aa3b0] flex-shrink-0 text-right">
                        <span class="hidden sm:inline">{{ miseEnPlace.length }} ingredientes</span>
                        <span class="sm:hidden">{{ miseEnPlace.length }} ing.</span>
                    </div>
                </div>

                <div class="px-4 md:px-5 py-2.5 bg-bg-soft border-b border-line-soft text-[11px] text-ink-soft">
                    Cantidades totales agregadas de todos los ingredientes necesarios para el servicio.
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] text-[13px]">
                        <thead>
                            <tr class="bg-bg-soft">
                                <SortableHeader sort-key="nombre" :current-sort="mepSortKey" :current-dir="mepSortDir" @sort="mepSortBy">Ingrediente</SortableHeader>
                                <SortableHeader sort-key="cantidad_bruta" align="right" :current-sort="mepSortKey" :current-dir="mepSortDir" @sort="mepSortBy">Cantidad</SortableHeader>
                                <SortableHeader sort-key="unidad" :current-sort="mepSortKey" :current-dir="mepSortDir" @sort="mepSortBy">Unidad</SortableHeader>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Usado en</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(ing, idx) in mepSorted"
                                :key="idx"
                                class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                                :class="ing.es_sustituto ? 'bg-danger-soft/40 hover:bg-danger-soft/60' : ''"
                            >
                                <td class="px-4 py-2.5" :class="ing.es_sustituto ? 'text-danger font-medium' : ''">
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="ing.es_sustituto"
                                            class="inline-block w-1.5 h-1.5 rounded-full bg-danger flex-shrink-0"
                                        ></span>
                                        {{ ing.nombre }}
                                    </div>
                                    <div v-if="ing.es_sustituto" class="text-[10px] uppercase tracking-wider text-danger/70 mt-0.5 font-semibold">
                                        Sustituto · versión adaptada
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 font-mono text-right font-medium">{{ ing.cantidad }}</td>
                                <td class="px-4 py-2.5 font-mono text-ink-mute text-[11px]">{{ ing.unidad }}</td>
                                <td class="px-4 py-2.5 text-xs text-ink-soft">{{ resumenUso(ing.usado_en) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BLOQUES POR RECETA -->
            <div
                v-for="receta in resultado.recetas"
                :key="receta.id"
                class="bg-bg-panel border border-line rounded-sm mb-4 overflow-hidden"
            >
                <div class="px-4 md:px-5 py-3 md:py-3.5 bg-bg-header text-white flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                    <h3 class="text-[15px] font-semibold flex items-center gap-2.5 min-w-0">
                        <span class="truncate">{{ receta.nombre }}</span>
                        <span class="font-mono text-[11px] text-[#9aa3b0] font-normal bg-white/10 px-1.5 py-0.5 rounded-sm flex-shrink-0">{{ receta.id }}</span>
                    </h3>
                    <div class="font-mono text-xs text-[#9aa3b0]">
                        {{ receta.raciones_estandar }} estándar<span v-if="receta.raciones_adaptadas_total > 0"> + {{ receta.raciones_adaptadas_total }} adaptada{{ receta.raciones_adaptadas_total === 1 ? '' : 's' }}</span>
                    </div>
                </div>

                <div v-if="avisosPorReceta[receta.id]" class="border-b border-line-soft">
                    <div
                        v-for="aviso in avisosPorReceta[receta.id]"
                        :key="aviso.id"
                        :id="aviso.id"
                        class="px-4 md:px-5 py-2.5 flex items-start gap-3 text-xs border-b border-line-soft last:border-b-0 transition-all"
                        :class="{
                            'bg-danger-soft/60 border-l-[3px] border-l-danger': aviso.tipo === 'danger',
                            'bg-warn-soft/60 border-l-[3px] border-l-warn': aviso.tipo === 'warn',
                            'bg-ok-soft/60 border-l-[3px] border-l-ok': aviso.tipo === 'ok',
                        }"
                    >
                        <span
                            class="font-mono font-bold text-xs leading-none mt-0.5"
                            :class="{
                                'text-danger': aviso.tipo === 'danger',
                                'text-warn': aviso.tipo === 'warn',
                                'text-ok': aviso.tipo === 'ok',
                            }"
                        >{{ iconoAviso[aviso.tipo] }}</span>
                        <div>
                            <strong class="font-semibold">{{ aviso.titulo }}.</strong>
                            <span class="ml-1">{{ aviso.mensaje }}</span>
                        </div>
                    </div>
                </div>

                <IngredientesTabla
                    titulo="Ingredientes · receta estándar"
                    :subtitulo="receta.raciones_estandar + ' raciones · merma aplicada'"
                    :ingredientes="receta.ingredientes_estandar"
                />

                <div
                    v-for="(version, vIdx) in receta.versiones_adaptadas"
                    :key="vIdx"
                    class="border-b border-line-soft"
                >
                    <div class="px-4 md:px-5 py-2.5 bg-bg-soft border-b border-line-soft flex justify-between items-center">
                        <h4 class="text-[11px] uppercase tracking-wider font-semibold text-danger flex items-center gap-2">
                            Adaptada · {{ version.alergenos_evitados.map(a => `sin ${a}`).join(', ') }}
                        </h4>
                        <span class="text-[11px] text-ink-mute font-mono">{{ version.raciones }} raciones</span>
                    </div>

                    <div v-if="version.sin_solucion" class="px-4 md:px-5 py-4">
                        <AlertBox tipo="danger" titulo="Sin solución" :mensaje="version.aviso" />
                    </div>

                    <IngredientesTabla
                        v-else
                        :ingredientes="version.ingredientes"
                        :sin-cabecera="true"
                    />
                </div>
            </div>

            <!-- PLANIFICACIÓN TEMPORAL -->
            <div class="bg-bg-panel border border-line rounded-sm mb-4 overflow-hidden">
                <div class="px-4 md:px-5 py-3 md:py-3.5 bg-bg-header text-white flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                    <h3 class="text-[15px] font-semibold">Planificación temporal</h3>
                    <div class="font-mono text-xs text-[#9aa3b0]">
                        Desde hora servicio {{ resumen.hora_servicio }}
                    </div>
                </div>

                <div class="px-4 md:px-5 py-2.5 bg-bg-soft border-b border-line-soft text-[11px] text-ink-soft">
                    Marca cada tarea según la vas completando. Los avisos aparecen junto a la tarea que afectan.
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-[13px]">
                        <thead>
                            <tr class="bg-bg-soft">
                                <th class="px-4 py-2 border-b border-line w-10"></th>
                                <SortableHeader sort-key="hora_inicio" :current-sort="planSortKey" :current-dir="planSortDir" @sort="planSortBy">Hora</SortableHeader>
                                <SortableHeader sort-key="descripcion" :current-sort="planSortKey" :current-dir="planSortDir" @sort="planSortBy">Tarea</SortableHeader>
                                <SortableHeader sort-key="equipo_nombre" :current-sort="planSortKey" :current-dir="planSortDir" @sort="planSortBy">Equipo</SortableHeader>
                                <SortableHeader sort-key="duracion_minutos" align="right" :current-sort="planSortKey" :current-dir="planSortDir" @sort="planSortBy">Duración</SortableHeader>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(tarea, idx) in planSorted" :key="tareaKey(tarea, idx)">
                                <tr
                                    class="border-b border-line-soft transition-all"
                                    :class="[
                                        tarea.es_final ? '!bg-ok-soft' : 'even:bg-bg-row-alt',
                                        tareasCompletadas.has(tareaKey(tarea, idx)) ? 'opacity-50 bg-bg-soft/60' : '',
                                        bordeFila(tipoAvisoMasGrave(tarea.elaboracion_id, avisosPorTarea))
                                    ]"
                                >
                                    <td class="px-4 py-2.5 w-10">
                                        <input
                                            v-if="!tarea.es_final"
                                            type="checkbox"
                                            :checked="tareasCompletadas.has(tareaKey(tarea, idx))"
                                            @change="toggleTarea(tareaKey(tarea, idx))"
                                            @click.stop
                                            class="w-[18px] h-[18px] rounded-sm border-line-strong cursor-pointer accent-accent"
                                        />
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-mono font-semibold text-[14px] w-20 relative"
                                        :class="tareasCompletadas.has(tareaKey(tarea, idx)) ? 'after:absolute after:left-3 after:right-3 after:top-1/2 after:h-px after:bg-ink/60' : ''"
                                    >
                                        {{ tarea.hora_inicio }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 relative"
                                        :class="[
                                            tarea.es_final ? 'text-ok font-semibold' : 'font-medium',
                                            tareasCompletadas.has(tareaKey(tarea, idx)) ? 'after:absolute after:left-2 after:right-2 after:top-1/2 after:h-px after:bg-ink/60' : ''
                                        ]"
                                    >
                                        {{ tarea.descripcion }}
                                        <Badge v-if="tarea.tandas > 1" variant="tanda" class="ml-2">{{ tarea.tandas }} tandas</Badge>
                                        <Badge v-if="tarea.tiene_versiones" variant="versiones" class="ml-2">2 versiones</Badge>
                                        <div v-if="tarea.receta_nombre && !tarea.es_final" class="text-[11px] text-ink-mute font-normal mt-0.5">
                                            {{ tarea.receta_nombre }}<span v-if="tarea.temperatura"> · {{ tarea.temperatura }}°C</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-xs text-ink-soft w-36 relative"
                                        :class="tareasCompletadas.has(tareaKey(tarea, idx)) ? 'after:absolute after:left-3 after:right-3 after:top-1/2 after:h-px after:bg-ink/60' : ''"
                                    >
                                        {{ tarea.equipo_nombre || '—' }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-mono text-right text-xs text-ink-soft w-20 relative"
                                        :class="tareasCompletadas.has(tareaKey(tarea, idx)) ? 'after:absolute after:left-3 after:right-3 after:top-1/2 after:h-px after:bg-ink/60' : ''"
                                    >
                                        <span v-if="tarea.duracion_minutos > 0">{{ tarea.duracion_minutos }} min</span>
                                        <span v-else>—</span>
                                    </td>
                                </tr>

                                <tr
                                    v-for="aviso in (avisosPorTarea[tarea.elaboracion_id] || [])"
                                    :key="aviso.id"
                                    :id="aviso.id"
                                    class="transition-all"
                                    :class="{
                                        'bg-danger-soft/50': aviso.tipo === 'danger',
                                        'bg-warn-soft/50': aviso.tipo === 'warn',
                                        'bg-ok-soft/50': aviso.tipo === 'ok',
                                        'opacity-50': tareasCompletadas.has(tareaKey(tarea, idx)),
                                    }"
                                >
                                    <td colspan="5" class="px-4 py-2 border-b border-line-soft">
                                        <div
                                            class="flex items-start gap-3 text-xs pl-2 md:pl-14"
                                            :class="{
                                                'text-danger': aviso.tipo === 'danger',
                                                'text-warn': aviso.tipo === 'warn',
                                                'text-ok': aviso.tipo === 'ok',
                                            }"
                                        >
                                            <span class="font-mono font-bold leading-none mt-0.5">{{ iconoAviso[aviso.tipo] }}</span>
                                            <div>
                                                <strong class="font-semibold">{{ aviso.titulo }}.</strong>
                                                <span class="ml-1">{{ aviso.mensaje }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RESUMEN DE AVISOS -->
            <div
                v-if="todosAvisos.length > 0"
                id="resumen-avisos"
                class="bg-bg-panel border border-line rounded-sm overflow-hidden"
            >
                <div class="px-4 md:px-5 py-2.5 bg-bg-soft border-b border-line flex justify-between items-center">
                    <h4 class="text-[11px] uppercase tracking-wider text-ink-soft font-semibold">Resumen de avisos</h4>
                    <span class="text-[11px] text-ink-mute font-mono">{{ todosAvisos.length }} {{ todosAvisos.length === 1 ? 'aviso' : 'avisos' }}</span>
                </div>

                <ul class="divide-y divide-line-soft">
                    <li
                        v-for="aviso in todosAvisos"
                        :key="aviso.id"
                        @click="irAAviso(aviso)"
                        class="px-4 md:px-5 py-2.5 flex items-center gap-2 md:gap-3 text-xs cursor-pointer hover:bg-bg-soft transition-colors"
                    >
                        <span
                            class="text-[9px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-sm flex-shrink-0 w-14 md:w-16 text-center"
                            :class="{
                                'bg-danger text-white': aviso.tipo === 'danger',
                                'bg-warn text-white': aviso.tipo === 'warn',
                                'bg-ok text-white': aviso.tipo === 'ok',
                            }"
                        >
                            {{ aviso.tipo === 'danger' ? 'Crítico' : aviso.tipo === 'warn' ? 'Aviso' : 'Info' }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <strong class="font-semibold">{{ aviso.titulo }}</strong>
                            <span class="text-ink-soft ml-1 hidden sm:inline">· {{ aviso.referencia_nombre }}</span>
                            <div class="text-ink-soft text-[11px] sm:hidden">{{ aviso.referencia_nombre }}</div>
                        </div>
                        <span class="text-accent text-xs flex-shrink-0">→</span>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
