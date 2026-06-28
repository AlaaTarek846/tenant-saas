import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '@/services/api';
import {
    ADMIN_ROLES,
    API_ROUTES,
    AUTH_MODES,
    COMPANY_PERMISSIONS,
    ROLES,
    STORAGE_KEYS,
} from '@/utils/constants';
import {
    clearStorageItems,
    getStorageItem,
    removeStorageItem,
    setStorageItem,
} from '@/utils/storage';
import { unwrapApiData, unwrapApiMessage } from '@/utils/apiResponse';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(getStorageItem(STORAGE_KEYS.USER));
    const token = ref(getStorageItem(STORAGE_KEYS.TOKEN));
    const authMode = ref(getStorageItem(STORAGE_KEYS.AUTH_MODE, AUTH_MODES.SANCTUM));
    const loading = ref(false);
    const initialized = ref(false);

    const isAuthenticated = computed(() => Boolean(user.value));
    const isEmailVerified = computed(() => Boolean(user.value?.email_verified_at ?? user.value?.email_verified));
    const isTokenAuth = computed(() => authMode.value === AUTH_MODES.TOKEN);

    const userRoles = computed(() => {
        const roles = user.value?.roles;

        if (Array.isArray(roles)) {
            return roles.map((role) => (typeof role === 'string' ? role : role.name));
        }

        return [];
    });

    const isSuperAdmin = computed(() => hasRole(ROLES.SUPER_ADMIN));
    const isCompanyAdmin = computed(() => hasRole(ROLES.COMPANY_ADMIN));
    const isAdmin = computed(() => hasAnyRole(ADMIN_ROLES) || hasAnyPermission(COMPANY_PERMISSIONS));

    const userPermissions = computed(() => {
        const permissions = user.value?.permissions ?? [];

        return permissions.map((permission) => (
            typeof permission === 'string'
                ? { name: permission, group_category: permission.split('_').slice(1).join('_') || null }
                : permission
        ));
    });

    function hasRole(role) {
        return userRoles.value.includes(role);
    }

    function hasAnyRole(roles) {
        return roles.some((role) => hasRole(role));
    }

    function hasPermission(permission) {
        return userPermissions.value.some((item) => item.name === permission);
    }

    function hasAnyPermission(permissions) {
        return permissions.some((permission) => hasPermission(permission));
    }

    function persistAuth() {
        if (user.value) {
            setStorageItem(STORAGE_KEYS.USER, user.value);
        } else {
            removeStorageItem(STORAGE_KEYS.USER);
        }

        if (token.value) {
            setStorageItem(STORAGE_KEYS.TOKEN, token.value);
        } else {
            removeStorageItem(STORAGE_KEYS.TOKEN);
        }

        setStorageItem(STORAGE_KEYS.AUTH_MODE, authMode.value);
    }

    function setAuth(payload) {
        user.value = payload.user ?? user.value;
        token.value = payload.token ?? token.value;
        authMode.value = payload.authMode ?? authMode.value;
        persistAuth();
    }

    function clearAuth() {
        user.value = null;
        token.value = null;
        authMode.value = AUTH_MODES.SANCTUM;
        clearStorageItems([
            STORAGE_KEYS.USER,
            STORAGE_KEYS.TOKEN,
            STORAGE_KEYS.AUTH_MODE,
        ]);
    }

    async function initCsrfCookie() {
        await api.get(API_ROUTES.CSRF_COOKIE);
    }

    async function fetchUser() {
        try {
            const response = await api.get(API_ROUTES.USER);
            const payload = unwrapApiData(response);
            setAuth({ user: payload.user ?? payload });
            return payload.user ?? payload;
        } catch {
            clearAuth();
            return null;
        }
    }

    async function initialize() {
        if (initialized.value) {
            return;
        }

        loading.value = true;

        try {
            if (isTokenAuth.value && token.value) {
                await fetchUser();
            } else if (user.value) {
                await initCsrfCookie();
                await fetchUser();
            }
        } finally {
            loading.value = false;
            initialized.value = true;
        }
    }

    async function login(credentials) {
        loading.value = true;

        try {
            await initCsrfCookie();
            authMode.value = AUTH_MODES.SANCTUM;

            const response = await api.post(API_ROUTES.LOGIN, credentials);
            const payload = unwrapApiData(response);
            setAuth({ user: payload.user ?? payload, authMode: AUTH_MODES.SANCTUM });

            await fetchUser();

            return payload;
        } finally {
            loading.value = false;
        }
    }

    async function loginWithToken(credentials) {
        loading.value = true;

        try {
            authMode.value = AUTH_MODES.TOKEN;

            const response = await api.post(API_ROUTES.LOGIN, credentials);
            const payload = unwrapApiData(response);
            const accessToken = payload.token ?? payload.access_token;

            setAuth({
                user: payload.user ?? payload,
                token: accessToken,
                authMode: AUTH_MODES.TOKEN,
            });

            return payload;
        } finally {
            loading.value = false;
        }
    }

    async function register(payload) {
        loading.value = true;

        try {
            await initCsrfCookie();

            const response = await api.post(API_ROUTES.REGISTER, payload);
            const data = unwrapApiData(response);
            setAuth({
                user: data.user ?? data,
                token: data.token ?? null,
                authMode: data.token ? AUTH_MODES.TOKEN : AUTH_MODES.SANCTUM,
            });

            return data;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        loading.value = true;

        try {
            if (isAuthenticated.value) {
                await api.post(API_ROUTES.LOGOUT);
            }
        } catch {
            // Always clear local state even if the server request fails.
        } finally {
            clearAuth();
            loading.value = false;
        }
    }

    async function forgotPassword(payload) {
        loading.value = true;

        try {
            await initCsrfCookie();

            const response = await api.post(API_ROUTES.FORGOT_PASSWORD, payload);

            return {
                message: unwrapApiMessage(response),
                data: unwrapApiData(response),
            };
        } finally {
            loading.value = false;
        }
    }

    async function resetPassword(payload) {
        loading.value = true;

        try {
            await initCsrfCookie();

            const response = await api.post(API_ROUTES.RESET_PASSWORD, payload);

            return {
                message: unwrapApiMessage(response),
                data: unwrapApiData(response),
            };
        } finally {
            loading.value = false;
        }
    }

    async function resendVerificationEmail() {
        const response = await api.post(API_ROUTES.RESEND_VERIFICATION);

        return unwrapApiMessage(response);
    }

    async function verifyCode(code) {
        loading.value = true;

        try {
            const response = await api.post(API_ROUTES.VERIFY_CODE, { code });
            const payload = unwrapApiData(response);
            setAuth({ user: payload.user ?? payload });

            return payload;
        } finally {
            loading.value = false;
        }
    }

    async function resendVerifyCode() {
        const response = await api.post(API_ROUTES.RESEND_VERIFY_CODE);
        const payload = unwrapApiData(response);
        setAuth({ user: payload.user ?? payload });

        return payload;
    }

    return {
        user,
        token,
        authMode,
        loading,
        initialized,
        isAuthenticated,
        isEmailVerified,
        isTokenAuth,
        userRoles,
        userPermissions,
        isSuperAdmin,
        isCompanyAdmin,
        isAdmin,
        hasRole,
        hasAnyRole,
        hasPermission,
        hasAnyPermission,
        setAuth,
        clearAuth,
        initialize,
        fetchUser,
        login,
        loginWithToken,
        register,
        logout,
        forgotPassword,
        resetPassword,
        resendVerificationEmail,
        verifyCode,
        resendVerifyCode,
    };
});
