<script setup>
import { storeToRefs } from 'pinia';
import { useToastStore } from '@/stores/toast';

const toastStore = useToastStore();
const { items } = storeToRefs(toastStore);

function iconClass(severity) {
    const map = {
        success: 'bx-check-circle text-success',
        error: 'bx-error-circle text-danger',
        info: 'bx-info-circle text-info',
        warn: 'bx-error text-warning',
    };

    return map[severity] ?? 'bx-info-circle text-info';
}

function alertClass(severity) {
    const map = {
        success: 'alert-success',
        error: 'alert-danger',
        info: 'alert-info',
        warn: 'alert-warning',
    };

    return map[severity] ?? 'alert-info';
}
</script>

<template>
    <div class="app-toast-container" aria-live="polite" aria-atomic="true">
        <TransitionGroup name="toast">
            <div
                v-for="item in items"
                :key="item.id"
                class="alert app-toast shadow-sm"
                :class="alertClass(item.severity)"
                role="alert"
            >
                <div class="d-flex align-items-start gap-2">
                    <i class="bx font-size-18 mt-1" :class="iconClass(item.severity)"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-1">{{ item.summary }}</strong>
                        <span>{{ item.detail }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" @click="toastStore.dismiss(item.id)"></button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.app-toast-container {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 10050;
    width: min(22rem, calc(100vw - 2rem));
    pointer-events: none;
}

.app-toast {
    pointer-events: auto;
    margin-bottom: 0.75rem;
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-0.5rem);
}
</style>
