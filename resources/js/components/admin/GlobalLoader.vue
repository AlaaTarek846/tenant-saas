<script setup>
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { isLoading, booting, httpPending } = storeToRefs(appStore);
</script>

<template>
    <div v-if="httpPending && !booting" class="http-progress-bar" aria-hidden="true"></div>

    <Transition name="fade">
        <div v-if="isLoading" class="global-loader" :class="{ 'global-loader--boot': booting }">
            <div class="global-loader__panel">
                <div class="spinner-border text-primary global-loader__spinner" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="global-loader__text mb-0">{{ booting ? 'جاري تحميل التطبيق...' : 'جاري التحميل...' }}</p>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.global-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(2px);
}

.global-loader--boot {
    background: #f8f9fa;
}

.global-loader__panel {
    text-align: center;
    padding: 2rem;
}

.global-loader__spinner {
    width: 2.75rem;
    height: 2.75rem;
    margin-bottom: 1rem;
}

.global-loader__text {
    color: #495057;
    font-size: 0.95rem;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.http-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    z-index: 10040;
    background: linear-gradient(90deg, #556ee6, #34c38f);
    animation: http-progress 1s ease-in-out infinite;
}

@keyframes http-progress {
    0% { opacity: 0.45; }
    50% { opacity: 1; }
    100% { opacity: 0.45; }
}
</style>
