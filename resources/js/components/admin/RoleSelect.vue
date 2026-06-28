<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'اختر الدور',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    invalid: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <PrimeSelect
        v-model="selected"
        :options="options"
        option-label="label"
        option-value="name"
        :placeholder="placeholder"
        append-to="body"
        :disabled="disabled"
        :invalid="invalid"
        show-clear
        filter
        class="w-100 prime-rtl"
        empty-message="لا توجد أدوار"
        empty-filter-message="لا توجد نتائج"
    />
</template>

<style scoped>
:deep(.prime-rtl.p-select) {
    width: 100%;
}

:deep(.p-select-label) {
    text-align: right;
}
</style>
