<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import LayoutHeader from '@/components/layouts/Header.vue';
import LayoutSidebar from '@/components/layouts/Sidebar.vue';
import LayoutFooter from '@/components/layouts/Footer.vue';
import { initMetisMenu, loadDashboardTheme } from '@/composables/useSkoteAssets';

const props = defineProps({
    pageTitle: {
        type: String,
        default: '',
    },
});

const route = useRoute();

const homeRoute = 'admin.dashboard';

const title = computed(() => props.pageTitle || route.meta.title || 'لوحة التحكم');

onMounted(async () => {
    await loadDashboardTheme();
    initMetisMenu();
});
</script>

<template>
    <div id="layout-wrapper">
        <LayoutHeader :home-route="homeRoute" />
        <LayoutSidebar />

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">{{ title }}</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item">
                                            <router-link :to="{ name: homeRoute }">الرئيسية</router-link>
                                        </li>
                                        <li class="breadcrumb-item active">{{ title }}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <slot />
                </div>
            </div>

            <LayoutFooter />
        </div>
    </div>
</template>
