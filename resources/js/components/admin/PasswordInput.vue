<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: () => `password-${Math.random().toString(36).slice(2, 9)}`,
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    autocomplete: {
        type: String,
        default: 'new-password',
    },
    invalid: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);

const showPassword = ref(false);

const inputType = computed(() => (showPassword.value ? 'text' : 'password'));
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="form-label">{{ label }}</label>
        <div class="input-group auth-pass-inputgroup">
            <input
                :id="id"
                :value="modelValue"
                :type="inputType"
                class="form-control"
                :class="{ 'is-invalid': invalid }"
                :placeholder="placeholder"
                :required="required"
                :autocomplete="autocomplete"
                @input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                class="btn btn-light"
                type="button"
                :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                @click="showPassword = !showPassword"
            >
                <i :class="showPassword ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline'"></i>
            </button>
        </div>
        <p v-if="hint && !error" class="text-muted small mt-1 mb-0">{{ hint }}</p>
        <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
    </div>
</template>
