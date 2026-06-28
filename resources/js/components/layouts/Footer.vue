<script setup>
import { computed } from 'vue';

const appName = computed(() => import.meta.env.VITE_APP_NAME ?? 'Laravel');
const year = new Date().getFullYear();
</script>

<template>
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    {{ year }} © {{ appName }}.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        لوحة التحكم
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
