<script setup>
defineProps({
    sortKey: { type: String, required: true },
    currentSort: { type: String, default: null },
    currentDir: { type: String, default: null },
    align: { type: String, default: 'left' },
});

defineEmits(['sort']);
</script>

<template>
    <th
        @click="$emit('sort', sortKey)"
        class="text-[10px] uppercase tracking-wider text-ink-mute font-semibold px-4 py-2 border-b border-line cursor-pointer select-none hover:bg-line-soft transition-colors"
        :class="align === 'right' ? 'text-right' : 'text-left'"
    >
        <span class="inline-flex items-center gap-1.5" :class="align === 'right' ? 'justify-end w-full' : ''">
            <slot />
            <span
                class="text-[9px] font-mono transition-colors"
                :class="currentSort === sortKey ? 'text-accent' : 'text-transparent'"
            >
                <template v-if="currentSort === sortKey">
                    {{ currentDir === 'asc' ? '▲' : currentDir === 'desc' ? '▼' : '' }}
                </template>
                <template v-else>▲</template>
            </span>
        </span>
    </th>
</template>
