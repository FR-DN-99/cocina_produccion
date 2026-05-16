<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    escenario: { type: Object, required: true },
    recetasPorCategoria: { type: Object, required: true },
});

const seleccionadas = ref(new Set());

function toggle(recetaId) {
    if (seleccionadas.value.has(recetaId)) {
        seleccionadas.value.delete(recetaId);
    } else {
        seleccionadas.value.add(recetaId);
    }
}

const totalRecetas = computed(() => {
    return Object.values(props.recetasPorCategoria).reduce((acc, lista) => acc + lista.length, 0);
});

const numSeleccionadas = computed(() => seleccionadas.value.size);

function nombreCategoria(cat) {
    const map = {
        entrante: 'Entrantes',
        principal: 'Principales',
        postre: 'Postres',
        guarnicion: 'Guarniciones',
    };
    return map[cat] || cat.charAt(0).toUpperCase() + cat.slice(1) + 's';
}

function tiempoLegible(minutos) {
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    if (h === 0) return `${m} min`;
    if (m === 0) return `${h} h`;
    return `${h} h ${m} min`;
}

function calcular() {
    if (numSeleccionadas.value === 0) return;
    const recetas = Array.from(seleccionadas.value).join(',');
    router.get(
        `/escenarios/${props.escenario.id}/calcular`,
        { recetas },
    );
}

function etiquetaGrupo(grupo) {
    const map = {
        gluten: 'Celíacos',
        lactosa: 'Intolerantes a lactosa',
        huevo: 'Alérgicos a huevo',
        'gluten+lactosa': 'Gluten + lactosa',
    };
    return map[grupo.alergeno] || grupo.alergeno;
}

function colorDot(alergeno) {
    const base = alergeno.split('+')[0];
    return {
        gluten: 'bg-aler-gluten',
        lactosa: 'bg-aler-lactosa',
        huevo: 'bg-aler-huevo',
    }[base] || 'bg-ink-mute';
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
    <AppLayout :breadcrumb="[
        { label: 'Escenarios', href: '/' },
        { label: `${escenario.id} · Selección de recetas` }
    ]" :topbar-right="{ Servicio: escenario.hora_servicio }">
        <Link href="/"
            class="inline-flex items-center gap-1.5 text-xs text-ink-soft px-8 py-3 bg-bg-panel border-b border-line hover:text-accent transition-colors">
            ← Volver a escenarios
        </Link>

        <div class="grid min-h-[calc(100vh-52px-44px)]" style="grid-template-columns: 320px 1fr">
            <!-- SIDEBAR ESCENARIO -->
            <aside class="bg-bg-panel border-r border-line p-5 overflow-y-auto">
                <div class="pb-5 mb-5 border-b border-line-soft">
                    <div class="text-[10px] uppercase tracking-widest text-ink-mute mb-2 font-semibold">Escenario activo
                    </div>
                    <div class="text-[15px] font-semibold mb-1">{{ escenario.titulo }}</div>
                    <div class="text-xs text-ink-soft font-mono">{{ escenario.id }} · {{ escenario.tipo_servicio }} · {{
                        escenario.hora_servicio }}</div>
                </div>

                <div class="pb-5 mb-5 border-b border-line-soft">
                    <div class="text-[10px] uppercase tracking-widest text-ink-mute mb-2 font-semibold">Ocupación</div>
                    <div class="font-mono text-[32px] font-medium leading-none mb-1">{{
                        escenario.ocupacion.total_comensales }}</div>
                    <div class="text-[11px] text-ink-soft uppercase tracking-wider">Comensales totales</div>
                </div>

                <div class="pb-5 mb-5 border-b border-line-soft">
                    <div class="text-[10px] uppercase tracking-widest text-ink-mute mb-2 font-semibold">Alérgenos
                        declarados</div>
                    <div v-if="escenario.ocupacion.grupos_alergenos.length === 0" class="text-xs text-ok italic">
                        Sin alérgenos declarados
                    </div>
                    <div v-else class="flex flex-col gap-1">
                        <div v-for="(grupo, idx) in escenario.ocupacion.grupos_alergenos" :key="idx"
                            class="grid items-center gap-2.5 px-2 py-1.5 bg-bg-soft rounded-sm text-[13px]"
                            style="grid-template-columns: 14px 1fr auto">
                            <span class="w-2.5 h-2.5 rounded-full border-2 border-white shadow-[0_0_0_1px_#b8bec8]"
                                :class="colorDot(grupo.alergeno)"></span>
                            <span>{{ etiquetaGrupo(grupo) }}</span>
                            <span class="font-mono font-semibold">{{ grupo.personas }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] uppercase tracking-widest text-ink-mute mb-2 font-semibold">Otros datos
                    </div>
                    <div class="text-xs text-ink-soft leading-7">
                        <div v-if="escenario.ocupacion.pension_completa > 0">
                            Pensión completa: <strong class="font-mono text-ink">{{ escenario.ocupacion.pension_completa
                                }}</strong>
                        </div>
                        <div v-if="escenario.ocupacion.media_pension > 0">
                            Media pensión: <strong class="font-mono text-ink">{{ escenario.ocupacion.media_pension
                                }}</strong>
                        </div>
                        <div>Origen datos: <strong class="font-mono text-ink">Demo</strong></div>
                    </div>
                </div>
            </aside>

            <!-- PANEL PRINCIPAL: SELECCIÓN RECETAS -->
            <main class="p-6 bg-bg">
                <div class="flex justify-between items-center mb-1">
                    <h2 class="text-lg font-semibold">Selección de recetas</h2>
                    <div class="text-xs text-ink-soft">
                        <strong class="font-mono text-ink font-semibold">{{ numSeleccionadas }}</strong>
                        de {{ totalRecetas }} seleccionadas
                    </div>
                </div>
                <p class="text-[13px] text-ink-soft mb-6">
                    Marca las recetas a elaborar para este servicio. El sistema calculará cantidades y planificación
                    para todas las seleccionadas.
                </p>

                <div v-for="(recetas, categoria) in recetasPorCategoria" :key="categoria"
                    class="bg-bg-panel border border-line rounded-sm mb-4">
                    <div
                        class="px-4 py-2.5 bg-bg-soft border-b border-line text-[11px] uppercase tracking-wider text-ink-soft font-semibold flex justify-between">
                        <span>{{ nombreCategoria(categoria) }}</span>
                        <span class="font-mono text-ink-mute">{{ recetas.length }} disponibles</span>
                    </div>
                    <div v-for="receta in recetas" :key="receta.id" @click="toggle(receta.id)"
                        class="grid items-center gap-5 px-4 py-3.5 border-b border-line-soft last:border-b-0 cursor-pointer transition-colors"
                        :class="seleccionadas.has(receta.id) ? 'bg-accent-soft hover:bg-[#d5e3f0]' : 'hover:bg-bg-soft'"
                        style="grid-template-columns: 32px 1fr 200px 140px">
                        <div class="w-[18px] h-[18px] rounded-[3px] flex items-center justify-center text-[13px] text-white font-bold transition-all"
                            :class="seleccionadas.has(receta.id) ? 'bg-accent border border-accent' : 'border-[1.5px] border-line-strong bg-white'">
                            <span v-if="seleccionadas.has(receta.id)">✓</span>
                        </div>
                        <div>
                            <div class="text-sm font-medium">{{ receta.nombre }}</div>
                            <div class="font-mono text-[11px] text-ink-mute mt-0.5">{{ receta.id }} · {{
                                receta.numero_elaboraciones }} elaboraciones</div>
                        </div>
                        <div>
                            <div class="font-mono text-[13px] text-ink-soft">{{
                                tiempoLegible(receta.tiempo_total_minutos) }}</div>
                            <div class="text-[10px] uppercase tracking-wider text-ink-mute mt-px">total estimado</div>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            <span v-for="a in receta.alergenos" :key="a"
                                class="text-[10px] px-1.5 py-0.5 rounded-sm uppercase tracking-wider font-semibold border"
                                :class="chipAlergeno(a)">{{ a }}</span>
                        </div>
                    </div>
                </div>

                <!-- ACTION BAR -->
                <div
                    class="sticky bottom-0 bg-bg-panel border-t border-line px-6 py-3.5 flex justify-between items-center mt-6 -mx-6 -mb-6">
                    <div class="flex gap-6 text-xs text-ink-soft">
                        <div>Recetas: <strong class="font-mono text-ink font-semibold text-sm">{{ numSeleccionadas
                                }}</strong></div>
                        <div>Para: <strong class="font-mono text-ink font-semibold text-sm">{{
                            escenario.ocupacion.total_comensales }}</strong> comensales</div>
                        <div>Servicio: <strong class="font-mono text-ink font-semibold text-sm">{{
                                escenario.hora_servicio }}</strong></div>
                    </div>
                    <div class="flex gap-2.5">
                        <Link href="/" class="btn">Cancelar</Link>
                        <button @click="calcular" class="btn btn-primary" :disabled="numSeleccionadas === 0">
                            Calcular producción →
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>
