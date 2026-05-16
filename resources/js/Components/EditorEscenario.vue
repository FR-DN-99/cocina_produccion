<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from './Modal.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    escenario: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const totalComensales = ref(0);
const pensionCompleta = ref(0);
const mediaPension = ref(0);
const horaServicio = ref('14:00');
const gruposAlergenos = ref([]);
const guardando = ref(false);

const alergenosDisponibles = [
    { valor: 'gluten', etiqueta: 'Gluten (celíacos)' },
    { valor: 'lactosa', etiqueta: 'Lactosa' },
    { valor: 'huevo', etiqueta: 'Huevo' },
    { valor: 'gluten+lactosa', etiqueta: 'Gluten + lactosa' },
];

watch(() => props.escenario, (esc) => {
    if (!esc) return;
    totalComensales.value = esc.ocupacion.total_comensales;
    pensionCompleta.value = esc.ocupacion.pension_completa || 0;
    mediaPension.value = esc.ocupacion.media_pension || 0;
    horaServicio.value = esc.hora_servicio;
    // Copia profunda para no mutar el original
    gruposAlergenos.value = JSON.parse(JSON.stringify(esc.ocupacion.grupos_alergenos || []));
}, { immediate: true });

function añadirGrupo() {
    gruposAlergenos.value.push({ alergeno: 'gluten', personas: 1 });
}

function eliminarGrupo(idx) {
    gruposAlergenos.value.splice(idx, 1);
}

function guardar() {
    if (!props.escenario) return;
    guardando.value = true;
    router.post(
        `/escenarios/${props.escenario.id}/modificar`,
        {
            total_comensales: totalComensales.value,
            pension_completa: pensionCompleta.value,
            media_pension: mediaPension.value,
            hora_servicio: horaServicio.value,
            grupos_alergenos: gruposAlergenos.value,
        },
        {
            onFinish: () => {
                guardando.value = false;
                emit('close');
            },
        }
    );
}

function restaurar() {
    if (!props.escenario) return;
    if (!confirm('¿Restaurar el escenario a sus valores originales?')) return;
    guardando.value = true;
    router.post(
        `/escenarios/${props.escenario.id}/restaurar`,
        {},
        {
            onFinish: () => {
                guardando.value = false;
                emit('close');
            },
        }
    );
}

const totalAlergicos = () => gruposAlergenos.value.reduce((acc, g) => acc + Number(g.personas || 0), 0);
</script>

<template>
    <Modal
        :visible="visible"
        size="md"
        @close="$emit('close')"
    >
        <template #header>
            <h3 class="text-[15px] font-semibold">Modificar escenario</h3>
            <p v-if="escenario" class="text-xs text-[#9aa3b0] mt-0.5">
                {{ escenario.id }} · {{ escenario.titulo }}
            </p>
        </template>

        <div v-if="escenario" class="p-6 space-y-6">
            <div class="bg-warn-soft border-l-[3px] border-warn p-3 text-xs text-[#8a4708] rounded-r-sm">
                <strong>Atención:</strong> los cambios son temporales y solo se mantienen durante esta sesión. Al cerrar el navegador, el escenario vuelve a sus valores originales.
            </div>

            <!-- Hora del servicio -->
            <div>
                <label class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold block mb-2">Hora del servicio</label>
                <input
                    v-model="horaServicio"
                    type="time"
                    class="w-32 border border-line px-3 py-2 rounded-sm font-mono text-sm focus:outline-none focus:border-accent"
                />
            </div>

            <!-- Comensales -->
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold block mb-2">Total comensales</label>
                    <input
                        v-model.number="totalComensales"
                        type="number"
                        min="1"
                        class="w-full border border-line px-3 py-2 rounded-sm font-mono text-sm focus:outline-none focus:border-accent"
                    />
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold block mb-2">Pensión completa</label>
                    <input
                        v-model.number="pensionCompleta"
                        type="number"
                        min="0"
                        class="w-full border border-line px-3 py-2 rounded-sm font-mono text-sm focus:outline-none focus:border-accent"
                    />
                </div>
                <div>
                    <label class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold block mb-2">Media pensión</label>
                    <input
                        v-model.number="mediaPension"
                        type="number"
                        min="0"
                        class="w-full border border-line px-3 py-2 rounded-sm font-mono text-sm focus:outline-none focus:border-accent"
                    />
                </div>
            </div>

            <!-- Alérgenos -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold">Grupos de alérgenos</label>
                    <button
                        @click="añadirGrupo"
                        class="text-xs text-accent hover:text-[#174d8a] font-medium"
                    >
                        + Añadir grupo
                    </button>
                </div>

                <div v-if="gruposAlergenos.length === 0" class="text-xs text-ink-soft italic py-2">
                    Sin alérgenos declarados
                </div>

                <div
                    v-for="(grupo, idx) in gruposAlergenos"
                    :key="idx"
                    class="grid gap-2 mb-2 items-center"
                    style="grid-template-columns: 1fr 100px 40px"
                >
                    <select
                        v-model="grupo.alergeno"
                        class="border border-line px-3 py-2 rounded-sm text-sm focus:outline-none focus:border-accent"
                    >
                        <option
                            v-for="opt in alergenosDisponibles"
                            :key="opt.valor"
                            :value="opt.valor"
                        >{{ opt.etiqueta }}</option>
                    </select>
                    <input
                        v-model.number="grupo.personas"
                        type="number"
                        min="1"
                        placeholder="Personas"
                        class="border border-line px-3 py-2 rounded-sm font-mono text-sm focus:outline-none focus:border-accent"
                    />
                    <button
                        @click="eliminarGrupo(idx)"
                        class="text-danger hover:text-red-700 text-lg px-1"
                        aria-label="Eliminar"
                    >×</button>
                </div>

                <div v-if="totalAlergicos() > totalComensales" class="bg-danger-soft border-l-[3px] border-danger p-3 mt-3 text-xs text-danger rounded-r-sm">
                    <strong>Atención:</strong> el total de alérgicos ({{ totalAlergicos() }}) supera el número total de comensales ({{ totalComensales }}).
                </div>
            </div>
        </div>

        <template #footer>
            <button
                v-if="escenario?.modificado"
                @click="restaurar"
                class="btn"
                :disabled="guardando"
                style="border-color: #b8302a; color: #b8302a;"
            >Restaurar original</button>
            <button class="btn" @click="$emit('close')" :disabled="guardando">Cancelar</button>
            <button
                class="btn btn-primary"
                @click="guardar"
                :disabled="guardando || totalAlergicos() > totalComensales"
            >
                {{ guardando ? 'Guardando...' : 'Aplicar cambios' }}
            </button>
        </template>
    </Modal>
</template>
