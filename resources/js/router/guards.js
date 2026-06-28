import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import { loadAuthTheme, loadDashboardTheme } from '@/composables/useSkoteAssets';

function hideBootLoader() {
    document.getElementById('app-boot-loader')?.remove();
    document.getElementById('app')?.classList.add('app-ready');
}

async function ensureTheme(layout) {
    if (layout === 'admin') {
        await loadDashboardTheme();
        return;
    }

    if (layout === 'auth' || layout === 'error') {
        await loadAuthTheme();
    }
}

export function setupGuards(router) {
    router.beforeEach(async (to, from, next) => {
        const authStore = useAuthStore();
        const appStore = useAppStore();
        const isFirstNavigation = !from.name;
        const layoutChanged = to.meta.layout !== from.meta?.layout;

        if (isFirstNavigation || layoutChanged) {
            appStore.setBooting(true);
        }

        if (!authStore.initialized) {
            appStore.setGlobalLoading(true);

            try {
                await authStore.initialize();
            } finally {
                appStore.setGlobalLoading(false);
            }
        }

        try {
            await ensureTheme(to.meta.layout);
        } finally {
            appStore.setBooting(false);
            hideBootLoader();
        }

        if (to.meta.title) {
            appStore.setPageTitle(to.meta.title);
            document.title = `${to.meta.title} | ${import.meta.env.VITE_APP_NAME ?? 'App'}`;
        }

        if (to.meta.requiresAuth && !authStore.isAuthenticated) {
            return next({
                name: 'login',
                query: { redirect: to.fullPath },
            });
        }

        if (authStore.isAuthenticated && !authStore.isEmailVerified) {
            if (to.name !== 'verify-code') {
                return next({ name: 'verify-code' });
            }

            return next();
        }

        if (to.name === 'verify-code' && authStore.isEmailVerified) {
            return next(authStore.isAdmin ? { name: 'admin.dashboard' } : { name: 'error-403' });
        }

        if (to.meta.requiresAdmin && !authStore.isAdmin) {
            return next({ name: 'error-403' });
        }

        if (to.meta.roles?.length && !authStore.hasAnyRole(to.meta.roles)) {
            return next({ name: 'error-403' });
        }

        if (to.meta.permissions?.length && !authStore.hasAnyPermission(to.meta.permissions)) {
            return next({ name: 'error-403' });
        }

        if (to.meta.guest && authStore.isAuthenticated) {
            if (!authStore.isEmailVerified) {
                return next({ name: 'verify-code' });
            }

            const redirect = to.query.redirect
                ?? (authStore.isAdmin ? { name: 'admin.dashboard' } : { name: 'error-403' });

            return next(redirect);
        }

        return next();
    });
}
