<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'بحث...',
    },
});

const emit = defineEmits(['update:modelValue', 'search']);

const search = ref(props.modelValue);

watch(() => props.modelValue, (value) => {
    search.value = value;
});

function onInput() {
    emit('update:modelValue', search.value);
    emit('search', search.value);
}
</script>

<template>
    <div class="row align-items-center g-2">
        <div class="col-md-4">
            <input
                v-model="search"
                type="text"
                class="form-control"
                :placeholder="placeholder"
                @input="onInput"
            >
        </div>
        <div class="col-md-8">
            <slot />
        </div>
    </div>
</template>
