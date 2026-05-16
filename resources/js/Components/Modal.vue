<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'lg' }, // sm, md, lg, xl
});

const emit = defineEmits(['close']);

const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-2xl',
    lg: 'max-w-4xl',
    xl: 'max-w-6xl',
};

function handleKey(e) {
    if (e.key === 'Escape' && props.visible) {
        emit('close');
    }
}

onMounted(() => window.addEventListener('keydown', handleKey));
onUnmounted(() => window.removeEventListener('keydown', handleKey));

watch(() => props.visible, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="visible"
                class="fixed inset-0 z-[200] bg-black/50 flex items-start justify-center p-4 md:p-8 overflow-y-auto"
                @click.self="$emit('close')"
            >
                <div
                    class="bg-bg-panel border border-line rounded-sm shadow-2xl w-full my-auto"
                    :class="sizeClasses[size]"
                >
                    <div v-if="title || $slots.header" class="px-5 py-3.5 bg-bg-header text-white flex justify-between items-center">
                        <div>
                            <slot name="header">
                                <h3 class="text-[15px] font-semibold">{{ title }}</h3>
                                <p v-if="subtitle" class="text-xs text-[#9aa3b0] mt-0.5">{{ subtitle }}</p>
                            </slot>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="text-[#9aa3b0] hover:text-white transition-colors text-2xl leading-none px-1"
                            aria-label="Cerrar"
                        >×</button>
                    </div>

                    <div class="max-h-[80vh] overflow-y-auto">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="px-5 py-3 bg-bg-soft border-t border-line flex justify-end gap-2">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
