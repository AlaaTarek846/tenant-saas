<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'تأكيد',
    },
    message: {
        type: String,
        default: 'هل أنت متأكد؟',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <div
        class="modal fade"
        :class="{ show: show }"
        :style="{ display: show ? 'block' : 'none' }"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="btn-close" @click="emit('close')"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ message }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="emit('close')">
                        إلغاء
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger"
                        :disabled="loading"
                        @click="emit('confirm')"
                    >
                        {{ loading ? 'جاري الحذف...' : 'حذف' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>
