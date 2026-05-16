<script setup>
defineProps({
    variant: {
        type: String,
        default: 'normal',
        validator: (v) => ['normal', 'alerta', 'warn', 'evento', 'tanda', 'shared', 'versiones'].includes(v),
    },
});

const classMap = {
    normal: 'bg-bg-soft text-ink-soft border-line-strong',
    alerta: 'bg-danger-soft text-danger border-[#e8b6b1]',
    warn: 'bg-warn-soft text-warn border-[#e8c896]',
    evento: 'bg-accent-soft text-accent border-[#b8cfe5]',
    tanda: 'bg-warn-soft text-warn border-[#e8c896]',
    shared: 'bg-ok-soft text-ok border-[#b8d8be]',
    versiones: 'bg-accent-soft text-accent border-[#b8cfe5]',
};
</script>

<template>
    <span
        class="inline-flex items-center px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm font-semibold border"
        :class="classMap[variant]"
    >
        <slot />
    </span>
</template>
