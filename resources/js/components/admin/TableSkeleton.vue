<script setup>
defineProps({
    rows: { type: Number, default: 5 },
    columns: { type: Number, default: 7 },
});
</script>

<template>
    <tbody class="table-skeleton">
        <tr v-for="row in rows" :key="row">
            <td v-for="col in columns" :key="col">
                <span class="placeholder col-12 placeholder-wave rounded" :class="col === 1 ? 'placeholder-sm' : ''"></span>
            </td>
        </tr>
    </tbody>
</template>

<style scoped>
.table-skeleton td {
    vertical-align: middle;
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
}

.table-skeleton .placeholder {
    display: block;
    min-height: 0.9rem;
    opacity: 0.65;
}
</style>
