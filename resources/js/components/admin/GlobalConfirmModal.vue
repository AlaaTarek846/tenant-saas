<script setup>
import { storeToRefs } from 'pinia';
import { useConfirmStore } from '@/stores/confirm';

const confirmStore = useConfirmStore();
const { visible, title, message, confirmLabel, loading } = storeToRefs(confirmStore);
</script>

<template>
    <div
        class="modal fade"
        :class="{ show: visible }"
        :style="{ display: visible ? 'block' : 'none' }"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="btn-close" :disabled="loading" @click="confirmStore.cancel()"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ message }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" :disabled="loading" @click="confirmStore.cancel()">
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" :disabled="loading" @click="confirmStore.confirm()">
                        {{ loading ? 'جاري التنفيذ...' : confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="visible" class="modal-backdrop fade show"></div>
</template>
