<script setup>
defineProps({
    app: { type: Object, required: true },
    breadcrumb: { type: Array, default: () => [] },
    right: { type: Object, default: () => ({}) },
});

defineEmits(['abrir-catalogo', 'abrir-maquinaria']);
</script>

<template>
    <header class="bg-bg-header text-white px-3 md:px-6 h-[52px] flex justify-between items-stretch sticky top-0 z-50 border-b border-[#2d3a4d]">
        <div class="flex items-center gap-2 md:gap-6 min-w-0">
            <div class="flex items-center gap-2 md:gap-2.5 pr-3 md:pr-5 border-r border-[#2d3a4d] h-full flex-shrink-0">
                <div class="w-7 h-7 bg-accent flex items-center justify-center font-mono font-semibold text-[13px] text-white rounded-sm">
                    SCP
                </div>
                <div class="hidden sm:block">
                    <div class="font-semibold text-sm tracking-wide">{{ app.fullName.split(' ').slice(0,2).join(' ') }}</div>
                    <div class="text-[10px] font-normal text-[#9aa3b0] tracking-wider uppercase mt-px">{{ app.hotel }}</div>
                </div>
            </div>
            <nav v-if="breadcrumb.length" class="flex items-center gap-2 text-xs text-[#9aa3b0] min-w-0 overflow-hidden">
                <!-- En móvil: mostrar solo el último item del breadcrumb -->
                <template v-for="(item, idx) in breadcrumb" :key="idx">
                    <Link
                        v-if="item.href && idx < breadcrumb.length - 1"
                        :href="item.href"
                        class="hidden md:inline hover:text-white transition-colors whitespace-nowrap"
                    >
                        {{ item.label }}
                    </Link>
                    <span
                        v-else
                        :class="[
                            idx === breadcrumb.length - 1 ? 'text-white font-medium truncate' : 'hidden md:inline',
                        ]"
                    >
                        {{ item.label }}
                    </span>
                    <span v-if="idx < breadcrumb.length - 1" class="hidden md:inline text-[#4a5566]">/</span>
                </template>
            </nav>
        </div>

        <div class="flex items-center gap-1 md:gap-2 text-xs flex-shrink-0">
            <button
                @click="$emit('abrir-catalogo')"
                class="flex items-center gap-2 text-[#9aa3b0] hover:text-white transition-colors px-2 md:px-3 py-1 rounded-sm hover:bg-[#2d3a4d]"
                aria-label="Catálogo de recetas"
                title="Catálogo"
            >
                <span class="text-base">📋</span>
                <span class="font-medium text-xs hidden md:inline">Catálogo</span>
            </button>
            <button
                @click="$emit('abrir-maquinaria')"
                class="flex items-center gap-2 text-[#9aa3b0] hover:text-white transition-colors px-2 md:px-3 py-1 rounded-sm hover:bg-[#2d3a4d]"
                aria-label="Maquinaría"
                title="Maquinaría"
            >
                <span class="text-base">🍳</span>
                <span class="font-medium text-xs hidden md:inline">Maquinaría</span>
            </button>
            <div class="hidden lg:flex items-center gap-6 ml-4">
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
