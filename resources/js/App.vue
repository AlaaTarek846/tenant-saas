<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useRoute } from 'vue-router';
import { useAppStore } from '@/stores/app';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ErrorLayout from '@/layouts/ErrorLayout.vue';
import GlobalLoader from '@/components/admin/GlobalLoader.vue';
import GlobalConfirmModal from '@/components/admin/GlobalConfirmModal.vue';
import AppToast from '@/components/admin/AppToast.vue';

const route = useRoute();
const appStore = useAppStore();
const { booting } = storeToRefs(appStore);

const layout = computed(() => {
    const layoutName = route.meta.layout ?? 'default';

    switch (layoutName) {
        case 'auth':
            return AuthLayout;
        case 'admin':
            return AdminLayout;
        case 'error':
            return ErrorLayout;
        default:
            return null;
    }
});
</script>

<template>
    <GlobalLoader />
    <GlobalConfirmModal />
    <AppToast />

    <component :is="layout" v-if="layout && !booting">
        <router-view />
    </component>
    <router-view v-else-if="!layout && !booting" />
</template>
