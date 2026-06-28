<script setup>
import { computed, onMounted } from 'vue';
import { loadAuthTheme } from '@/composables/useSkoteAssets';

const appName = computed(() => import.meta.env.VITE_APP_NAME ?? 'Laravel');
const year = new Date().getFullYear();

onMounted(async () => {
    await loadAuthTheme();
});
</script>

<template>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <slot />

                    <div class="mt-5 text-center">
                        <p>
                            © {{ year }} {{ appName }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
