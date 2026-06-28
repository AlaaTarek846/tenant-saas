<script setup>
import { onMounted } from 'vue';
import { loadAuthTheme } from '@/composables/useSkoteAssets';

onMounted(async () => {
    await loadAuthTheme();
});
</script>

<template>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <slot />
        </div>
    </div>
</template>
