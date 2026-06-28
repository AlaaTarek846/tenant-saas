import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const sidebarOpen = ref(true);
    const pageTitle = ref('');
    const booting = ref(true);
    const globalLoading = ref(false);
    const httpPending = ref(0);

    const isLoading = computed(() => booting.value || globalLoading.value);

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function setPageTitle(title) {
        pageTitle.value = title;
    }

    function setBooting(value) {
        booting.value = value;
    }

    function setGlobalLoading(value) {
        globalLoading.value = value;
    }

    function startHttp() {
        httpPending.value += 1;
    }

    function endHttp() {
        httpPending.value = Math.max(0, httpPending.value - 1);
    }

    return {
        sidebarOpen,
        pageTitle,
        booting,
        globalLoading,
        httpPending,
        isLoading,
        toggleSidebar,
        setPageTitle,
        setBooting,
        setGlobalLoading,
        startHttp,
        endHttp,
    };
});
