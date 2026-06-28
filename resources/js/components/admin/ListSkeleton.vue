<script setup>
defineProps({
    rows: { type: Number, default: 5 },
    showSecondary: { type: Boolean, default: true },
});
</script>

<template>
    <ul class="list-group list-group-flush list-skeleton">
        <li v-for="row in rows" :key="row" class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <span class="placeholder placeholder-wave rounded" :class="showSecondary ? 'col-4' : 'col-8'"></span>
            <span v-if="showSecondary" class="placeholder placeholder-wave col-3 rounded"></span>
        </li>
    </ul>
</template>

<style scoped>
.list-skeleton .placeholder {
    display: block;
    min-height: 0.9rem;
    opacity: 0.65;
}
</style>
