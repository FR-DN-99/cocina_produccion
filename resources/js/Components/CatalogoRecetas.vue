<script setup>
import { ref, computed, watch } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const cargando = ref(false);
const recetas = ref([]);
const equipos = ref([]);
const recetaSeleccionada = ref(null);
const error = ref(null);

watch(() => props.visible, async (v) => {
    if (v && recetas.value.length === 0) {
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

const recetasPorCategoria = computed(() => {
    const grupos = {};
    for (const r of recetas.value) {
        if (!grupos[r.categoria]) grupos[r.categoria] = [];
        grupos[r.categoria].push(r);
    }
    return grupos;
});

function nombreCategoria(cat) {
    const map = {
        entrante: 'Entrantes',
        principal: 'Principales',
        postre: 'Postres',
        guarnicion: 'Guarniciones',
    };
    return map[cat] || cat.charAt(0).toUpperCase() + cat.slice(1) + 's';
}

function abrirReceta(receta) {
    recetaSeleccionada.value = receta;
}

function cerrarReceta() {
    recetaSeleccionada.value = null;
}

function cerrarTodo() {
    recetaSeleccionada.value = null;
    emit('close');
}

function nombreEquipo(tipo) {
    const e = equipos.value.find(eq => eq.id === tipo);
    return e ? e.nombre : tipo;
}

function tiempoLegible(minutos) {
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    if (h === 0) return `${m} min`;
    if (m === 0) return `${h} h`;
    return `${h} h ${m} min`;
}

function tiempoTotal(receta) {
    return receta.elaboraciones.reduce((acc, e) => acc + e.tiempo_minutos, 0);
}

function chipAlergeno(a) {
    return {
        gluten: 'bg-danger-soft text-aler-gluten border-[#e8b6b1]',
        lactosa: 'bg-accent-soft text-aler-lactosa border-[#b8cfe5]',
        huevo: 'bg-warn-soft text-aler-huevo border-[#e8c896]',
    }[a] || 'bg-bg-soft text-ink-soft border-line';
}
</script>

<template>
    <Modal
        :visible="visible && !recetaSeleccionada"
        size="xl"
        title="Catálogo de recetas"
        subtitle="Fichas técnicas disponibles"
        @close="cerrarTodo"
    >
        <div v-if="cargando" class="px-6 py-10 text-center text-ink-soft">
            Cargando catálogo...
        </div>

        <div v-else-if="error" class="px-6 py-10 text-center text-danger">
            {{ error }}
        </div>

        <div v-else class="p-4 md:p-6">
            <div
                v-for="(grupo, categoria) in recetasPorCategoria"
                :key="categoria"
                class="bg-bg-panel border border-line rounded-sm mb-4 overflow-hidden"
            >
                <div class="px-4 py-2.5 bg-bg-soft border-b border-line text-[11px] uppercase tracking-wider text-ink-soft font-semibold flex justify-between">
                    <span>{{ nombreCategoria(categoria) }}</span>
                    <span class="font-mono text-ink-mute">{{ grupo.length }}</span>
                </div>
                <div
                    v-for="r in grupo"
                    :key="r.id"
                    @click="abrirReceta(r)"
                    class="grid items-center gap-3 px-4 py-3 border-b border-line-soft last:border-b-0 cursor-pointer hover:bg-bg-soft transition-colors"
                    style="grid-template-columns: 1fr auto"
                >
                    <div class="min-w-0">
                        <div class="text-sm font-medium">{{ r.nombre }}</div>
                        <div class="font-mono text-[11px] text-ink-mute mt-0.5">
                            {{ r.id }} · {{ tiempoLegible(tiempoTotal(r)) }}
                        </div>
                        <div class="flex gap-1 flex-wrap mt-1.5">
                            <span
                                v-for="a in r.alergenos"
                                :key="a"
                                class="text-[10px] px-1.5 py-0.5 rounded-sm uppercase tracking-wider font-semibold border"
                                :class="chipAlergeno(a)"
                            >{{ a }}</span>
                        </div>
                    </div>
                    <div class="text-ink-mute text-lg flex-shrink-0">→</div>
                </div>
            </div>
        </div>
    </Modal>

    <Modal
        :visible="recetaSeleccionada !== null"
        size="lg"
        @close="cerrarReceta"
    >
        <template #header>
            <h3 class="text-[15px] font-semibold flex items-center gap-2.5 min-w-0">
                <span class="truncate">{{ recetaSeleccionada?.nombre }}</span>
                <span class="font-mono text-[11px] text-[#9aa3b0] font-normal bg-white/10 px-1.5 py-0.5 rounded-sm flex-shrink-0">{{ recetaSeleccionada?.id }}</span>
            </h3>
            <p class="text-xs text-[#9aa3b0] mt-0.5">
                Ficha técnica · {{ nombreCategoria(recetaSeleccionada?.categoria) }} · {{ tiempoLegible(tiempoTotal(recetaSeleccionada || { elaboraciones: [] })) }}
            </p>
        </template>

        <div v-if="recetaSeleccionada" class="p-4 md:p-6 space-y-5 md:space-y-6">
            <div v-if="recetaSeleccionada.alergenos.length > 0">
                <div class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold mb-2">Alérgenos presentes</div>
                <div class="flex gap-1.5 flex-wrap">
                    <span
                        v-for="a in recetaSeleccionada.alergenos"
                        :key="a"
                        class="text-[10px] px-2 py-0.5 rounded-sm uppercase tracking-wider font-semibold border"
                        :class="chipAlergeno(a)"
                    >{{ a }}</span>
                </div>
            </div>

            <div>
                <div class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold mb-2">Ingredientes por persona</div>
                <div class="overflow-x-auto border border-line rounded-sm">
                    <table class="w-full min-w-[500px] text-[13px]">
                        <thead>
                            <tr class="bg-bg-soft">
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Ingrediente</th>
                                <th class="text-right text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Cant.</th>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Ud.</th>
                                <th class="text-right text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Merma</th>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Alérg.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(ing, idx) in recetaSeleccionada.ingredientes"
                                :key="idx"
                                class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                            >
                                <td class="px-4 py-2.5">{{ ing.nombre }}</td>
                                <td class="px-4 py-2.5 font-mono text-right font-medium">{{ ing.cantidad }}</td>
                                <td class="px-4 py-2.5 font-mono text-ink-mute text-[11px]">{{ ing.unidad }}</td>
                                <td class="px-4 py-2.5 font-mono text-right text-[11px] text-ink-soft">
                                    {{ ing.merma > 0 ? Math.round(ing.merma * 100) + '%' : '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex gap-1 flex-wrap">
                                        <span
                                            v-for="a in (ing.alergenos || [])"
                                            :key="a"
                                            class="text-[9px] px-1.5 py-0.5 rounded-sm uppercase tracking-wider font-semibold border"
                                            :class="chipAlergeno(a)"
                                        >{{ a }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold mb-2">Proceso de elaboración</div>
                <div class="overflow-x-auto border border-line rounded-sm">
                    <table class="w-full min-w-[500px] text-[13px]">
                        <thead>
                            <tr class="bg-bg-soft">
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line w-10">#</th>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Tarea</th>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Equipo</th>
                                <th class="text-left text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Tipo</th>
                                <th class="text-right text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line">Tiempo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(elab, idx) in recetaSeleccionada.elaboraciones"
                                :key="elab.id"
                                class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                            >
                                <td class="px-4 py-2.5 font-mono text-ink-mute">{{ idx + 1 }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="font-medium">{{ elab.descripcion }}</div>
                                    <div v-if="elab.temperatura" class="text-[11px] text-ink-mute mt-0.5">{{ elab.temperatura }}°C</div>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-ink-soft">{{ nombreEquipo(elab.equipo) }}</td>
                                <td class="px-4 py-2.5 text-xs text-ink-soft capitalize">{{ elab.tipo }}</td>
                                <td class="px-4 py-2.5 font-mono text-right text-xs text-ink-soft">{{ elab.tiempo_minutos }} min</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="recetaSeleccionada.sustituciones_alergenos && Object.keys(recetaSeleccionada.sustituciones_alergenos).length > 0">
                <div class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold mb-2">Sustituciones por alérgeno</div>
                <div
                    v-for="(susts, alergeno) in recetaSeleccionada.sustituciones_alergenos"
                    :key="alergeno"
                    class="border border-line rounded-sm mb-2 overflow-hidden"
                >
                    <div class="px-4 py-2 bg-danger-soft border-b border-line-soft text-[11px] uppercase tracking-wider text-danger font-semibold">
                        Sin {{ alergeno }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] min-w-[400px]">
                            <tbody>
                                <tr
                                    v-for="(s, idx) in susts"
                                    :key="idx"
                                    class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                                >
                                    <td class="px-4 py-2 text-ink-soft">{{ s.ingrediente_original }}</td>
                                    <td class="px-4 py-2 text-ink-mute text-center w-12">→</td>
                                    <td class="px-4 py-2 font-medium">{{ s.sustituto }}</td>
                                    <td class="px-4 py-2 font-mono text-right text-ink-soft text-[11px]">{{ s.cantidad }} {{ s.unidad }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <button class="btn" @click="cerrarReceta">Volver al catálogo</button>
        </template>
    </Modal>
</template>
