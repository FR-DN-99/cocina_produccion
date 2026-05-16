<script setup>
import SortableHeader from './SortableHeader.vue';
import { useSortable } from '@/composables/useSortable.js';

const props = defineProps({
    ingredientes: { type: Array, required: true },
    titulo: { type: String, default: '' },
    subtitulo: { type: String, default: '' },
    sinCabecera: { type: Boolean, default: false },
});

const { sortedItems, sortBy, sortKey, sortDir } = useSortable(props.ingredientes);
</script>

<template>
    <div class="border-b border-line-soft last:border-b-0">
        <div v-if="!sinCabecera && (titulo || subtitulo)" class="px-5 py-2.5 bg-bg-soft border-b border-line-soft flex justify-between items-center">
            <h4 class="text-[11px] uppercase tracking-wider text-ink-soft font-semibold">{{ titulo }}</h4>
            <span class="text-[11px] text-ink-mute font-mono">{{ subtitulo }}</span>
        </div>

        <table class="w-full text-[13px]">
            <thead>
                <tr class="bg-bg-soft">
                    <SortableHeader sort-key="nombre" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Ingrediente</SortableHeader>
                    <SortableHeader sort-key="cantidad_bruta" align="right" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Cantidad</SortableHeader>
                    <SortableHeader sort-key="unidad" :current-sort="sortKey" :current-dir="sortDir" @sort="sortBy">Unidad</SortableHeader>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(ing, idx) in sortedItems"
                    :key="idx"
                    class="border-b border-line-soft last:border-b-0 even:bg-bg-row-alt"
                >
                    <td class="px-4 py-2.5" :class="ing.sustituido ? 'text-danger' : ''">
                        {{ ing.nombre }}
                        <div v-if="ing.sustituido" class="text-[11px] italic text-ink-mute mt-0.5">↳ sustituye a {{ ing.ingrediente_original }}</div>
                    </td>
                    <td class="px-4 py-2.5 font-mono text-right font-medium">{{ ing.cantidad }}</td>
                    <td class="px-4 py-2.5 font-mono text-ink-mute text-[11px]">{{ ing.unidad }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
