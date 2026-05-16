<script setup>
defineProps({
    app: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
    right: { type: Object, default: () => ({}) },
});

defineEmits(['abrir-catalogo', 'abrir-maquinaria']);
</script>

<template>
    <header class="bg-bg-header text-white px-6 h-[52px] flex justify-between items-stretch sticky top-0 z-50 border-b border-[#2d3a4d]">
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2.5 pr-5 border-r border-[#2d3a4d] h-full">
                <div class="w-7 h-7 bg-accent flex items-center justify-center font-mono font-semibold text-[13px] text-white rounded-sm">
                    SCP
                </div>
                <div>
                    <div class="font-semibold text-sm tracking-wide">{{ app.fullName.split(' ').slice(0,2).join(' ') }}</div>
                    <div class="text-[10px] font-normal text-[#9aa3b0] tracking-wider uppercase mt-px">{{ app.hotel }}</div>
                </div>
            </div>
            <nav v-if="breadcrumb.length" class="hidden md:flex items-center gap-2 text-xs text-[#9aa3b0]">
                <template v-for="(item, idx) in breadcrumb" :key="idx">
                    <Link
                        v-if="item.href && idx < breadcrumb.length - 1"
                        :href="item.href"
                        class="hover:text-white transition-colors"
                    >
                        {{ item.label }}
                    </Link>
                    <span v-else :class="idx === breadcrumb.length - 1 ? 'text-white font-medium' : ''">
                        {{ item.label }}
                    </span>
                    <span v-if="idx < breadcrumb.length - 1" class="text-[#4a5566]">/</span>
                </template>
            </nav>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <button
                @click="$emit('abrir-catalogo')"
                class="flex items-center gap-2 text-[#9aa3b0] hover:text-white transition-colors px-3 py-1 rounded-sm hover:bg-[#2d3a4d]"
            >
                <span class="text-base">📋</span>
                <span class="font-medium text-xs">Catálogo</span>
            </button>
            <button
                @click="$emit('abrir-maquinaria')"
                class="flex items-center gap-2 text-[#9aa3b0] hover:text-white transition-colors px-3 py-1 rounded-sm hover:bg-[#2d3a4d]"
            >
                <span class="text-base">🍳</span>
                <span class="font-medium text-xs">Maquinaría</span>
            </button>
            <div class="hidden md:flex items-center gap-6 ml-4">
                <div v-for="(value, label) in right" :key="label" class="flex flex-col items-end gap-px">
                    <div class="text-[10px] text-[#9aa3b0] uppercase tracking-wider">{{ label }}</div>
                    <div class="text-white font-medium font-mono">{{ value }}</div>
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 bg-[rgba(45,122,61,0.2)] border border-[rgba(45,122,61,0.5)] rounded-sm text-[11px] text-[#6fbf7d] uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 bg-[#6fbf7d] rounded-full"></span>
                    Activo
                </div>
            </div>
        </div>
    </header>
</template>
