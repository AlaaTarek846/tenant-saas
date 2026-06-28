<script setup>
import { ref } from 'vue';
import InitialsAvatar from '@/components/admin/InitialsAvatar.vue';

defineProps({
    label: {
        type: String,
        default: 'الصورة',
    },
    name: {
        type: String,
        default: '',
    },
    previewUrl: {
        type: String,
        default: null,
    },
    shape: {
        type: String,
        default: 'circle',
    },
});

const emit = defineEmits(['change']);

const fileInput = ref(null);

function openPicker() {
    fileInput.value?.click();
}

function onFileChange(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    const previewUrl = URL.createObjectURL(file);
    emit('change', { file, previewUrl });
}
</script>

<template>
    <div class="mb-4">
        <label class="form-label d-block">{{ label }}</label>
        <div class="d-flex align-items-center gap-3">
            <InitialsAvatar
                :name="name"
                :image-url="previewUrl"
                :shape="shape"
                :size="96"
            />
            <div>
                <input
                    ref="fileInput"
                    type="file"
                    class="d-none"
                    accept="image/*"
                    @change="onFileChange"
                >
                <button type="button" class="btn btn-soft-primary btn-sm" @click="openPicker">
                    <i class="bx bx-upload me-1"></i>
                    رفع صورة
                </button>
                <p class="text-muted small mb-0 mt-2">PNG أو JPG — حد أقصى 2MB</p>
            </div>
        </div>
    </div>
</template>
