import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import { AUTH_MODES } from '@/utils/constants';
import { notifyError } from '@/utils/apiHandler';

export function setupHttpInterceptors(router) {
    api.interceptors.request.use(
        (config) => {
            const appStore = useAppStore();
            const authStore = useAuthStore();

            if (!config.skipGlobalLoading) {
                appStore.startHttp();
            }

            if (authStore.authMode === AUTH_MODES.TOKEN && authStore.token) {
                config.headers.Authorization = `Bearer ${authStore.token}`;
            }

            return config;
        },
        (error) => {
            const appStore = useAppStore();

            if (!error.config?.skipGlobalLoading) {
                appStore.endHttp();
            }

            return Promise.reject(error);
        },
    );

    api.interceptors.response.use(
        (response) => {
            const appStore = useAppStore();

            if (!response.config?.skipGlobalLoading) {
                appStore.endHttp();
            }

            return response;
        },
        (error) => {
            const appStore = useAppStore();
            const status = error.response?.status;
            const isSilent = error.config?.skipGlobalLoading || error.config?.skipErrorToast;

            if (!error.config?.skipGlobalLoading) {
                appStore.endHttp();
            }

            if (status === 401) {
                const authStore = useAuthStore();
                authStore.clearAuth();

                if (router.currentRoute.value.meta?.requiresAuth) {
                    router.push({ name: 'login' });
                }
            } else if (status === 403 && !isSilent) {
                router.push({ name: 'error-403' });
            } else if (status === 409 && !isSilent) {
                notifyError(error);
            } else if (status === 500 && !isSilent) {
                notifyError(error);
            } else if (status === 503 && !isSilent) {
                router.push({ name: 'error-503' });
            } else if (!isSilent && status && status >= 400 && status !== 422 && status !== 409) {
                notifyError(error);
            }

            return Promise.reject(error);
        },
    );
}
