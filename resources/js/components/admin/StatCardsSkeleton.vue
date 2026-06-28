<script setup>
defineProps({
    count: { type: Number, default: 6 },
    columns: { type: String, default: 'col-md-4 col-sm-6' },
});
</script>

<template>
    <div class="row">
        <div v-for="n in count" :key="n" :class="columns">
            <div class="card mini-stats-wid mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <span class="placeholder placeholder-wave col-5 rounded mb-2 d-block"></span>
                            <span class="placeholder placeholder-wave col-3 rounded d-block"></span>
                        </div>
                        <span class="placeholder placeholder-wave rounded-circle stat-icon-skeleton"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat-icon-skeleton {
    width: 3rem;
    height: 3rem;
    flex-shrink: 0;
    opacity: 0.65;
}

.placeholder {
    min-height: 0.9rem;
    opacity: 0.65;
}
</style>
