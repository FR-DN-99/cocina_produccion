<script setup>
defineProps({
    tipo: {
        type: String,
        default: 'ok',
        validator: (v) => ['ok', 'warn', 'danger'].includes(v),
    },
    titulo: String,
    mensaje: String,
});

const wrapperClass = {
    ok: 'bg-ok-soft border-l-ok',
    warn: 'bg-warn-soft border-l-warn',
    danger: 'bg-danger-soft border-l-danger',
};

const tagClass = {
    ok: 'bg-ok text-white',
    warn: 'bg-warn text-white',
    danger: 'bg-danger text-white',
};

const tagText = {
    ok: 'Info',
    warn: 'Aviso',
    danger: 'Crítico',
};
</script>

<template>
    <div
        class="p-3 border-l-[3px] rounded-r-sm text-xs flex gap-3"
        :class="wrapperClass[tipo]"
    >
        <span
            class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded-sm flex-shrink-0 self-start"
            :class="tagClass[tipo]"
        >
            {{ tagText[tipo] }}
        </span>
        <div>
            <strong v-if="titulo" class="font-semibold">{{ titulo }}</strong>
            <span v-if="titulo && mensaje"> · </span>
            <span v-if="mensaje">{{ mensaje }}</span>
            <slot />
        </div>
    </div>
</template>
