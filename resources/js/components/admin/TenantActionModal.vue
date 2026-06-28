<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'إجراء على الشركة',
    },
    message: {
        type: String,
        default: 'اختر الإجراء المناسب:',
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
                    <p class="mb-3">{{ message }}</p>
                    <div class="d-grid gap-2">
                        <button
                            type="button"
                            class="btn btn-warning text-start"
                            :disabled="loading"
                            @click="emit('confirm', 'suspend')"
                        >
                            <i class="bx bx-pause-circle me-2"></i>
                            <strong>إيقاف الشركة</strong>
                            <span class="d-block small mt-1 opacity-75">
                                تغيير حالة الشركة والمستخدمين إلى موقوف، وإلغاء التفعيل مع رمز تحقق جديد
                            </span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger text-start"
                            :disabled="loading"
                            @click="emit('confirm', 'force')"
                        >
                            <i class="bx bx-trash me-2"></i>
                            <strong>حذف نهائي</strong>
                            <span class="d-block small mt-1 opacity-75">
                                مسح الشركة وجميع المستخدمين والأدوار والبيانات المرتبطة
                            </span>
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="emit('close')">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>
