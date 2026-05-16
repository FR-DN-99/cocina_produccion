<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TopBar from '../Components/TopBar.vue';
import CatalogoRecetas from '../Components/CatalogoRecetas.vue';
import CatalogoEquipos from '../Components/CatalogoEquipos.vue';

defineProps({
    breadcrumb: { type: Array, default: () => [] },
    topbarRight: { type: Object, default: () => ({}) },
});

const app = computed(() => usePage().props.app);
const catalogoVisible = ref(false);
const maquinariaVisible = ref(false);
</script>

<template>
    <div class="min-h-screen bg-bg">
        <TopBar
            :app="app"
            :breadcrumb="breadcrumb"
            :right="topbarRight"
            @abrir-catalogo="catalogoVisible = true"
            @abrir-maquinaria="maquinariaVisible = true"
        />
        <slot />
        <CatalogoRecetas
            :visible="catalogoVisible"
            @close="catalogoVisible = false"
        />
        <CatalogoEquipos
            :visible="maquinariaVisible"
            @close="maquinariaVisible = false"
        />
    </div>
</template>
