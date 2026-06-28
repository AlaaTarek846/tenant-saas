<script setup>
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { initMetisMenu } from '@/composables/useSkoteAssets';
import { buildAdminNavItems } from '@/utils/adminNav';

const route = useRoute();
const authStore = useAuthStore();

const navItems = computed(() => buildAdminNavItems(authStore));

function isActive(name) {
    return route.name === name;
}

onMounted(() => {
    initMetisMenu();
});

watch(navItems, () => {
    initMetisMenu();
}, { deep: true });
</script>

<template>
    <div class="vertical-menu">
        <div data-simplebar class="h-100">
            <div id="sidebar-menu">
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li class="menu-title">القائمة</li>

                    <li
                        v-for="item in navItems"
                        :key="item.name"
                        :class="{ 'mm-active': isActive(item.name) }"
                    >
                        <router-link
                            :to="{ name: item.name }"
                            class="waves-effect"
                            :class="{ active: isActive(item.name) }"
                        >
                            <i :class="item.icon"></i>
                            <span>{{ item.label }}</span>
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
