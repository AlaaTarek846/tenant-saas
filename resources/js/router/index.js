import { createRouter, createWebHistory } from 'vue-router';
import { setupGuards } from '@/router/guards';
import { adminRoutes } from '@/router/admin';

const routes = [
    {
        path: '/',
        redirect: { name: 'login' },
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: {
            layout: 'auth',
            guest: true,
            title: 'تسجيل الدخول',
        },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: {
            layout: 'auth',
            guest: true,
            title: 'تسجيل شركة',
        },
    },
    {
        path: '/verify-code',
        name: 'verify-code',
        component: () => import('@/pages/auth/VerifyCode.vue'),
        meta: {
            layout: 'auth',
            requiresAuth: true,
            title: 'تأكيد الحساب',
        },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPassword.vue'),
        meta: {
            layout: 'auth',
            guest: true,
            title: 'نسيت كلمة المرور',
        },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('@/pages/auth/ResetPassword.vue'),
        meta: {
            layout: 'auth',
            guest: true,
            title: 'إعادة تعيين كلمة المرور',
        },
    },
    ...adminRoutes,
    {
        path: '/403',
        name: 'error-403',
        component: () => import('@/pages/errors/403.vue'),
        meta: {
            layout: 'error',
            title: 'غير مسموح',
        },
    },
    {
        path: '/500',
        name: 'error-500',
        component: () => import('@/pages/errors/500.vue'),
        meta: {
            layout: 'error',
            title: 'خطأ في الخادم',
        },
    },
    {
        path: '/503',
        name: 'error-503',
        component: () => import('@/pages/errors/503.vue'),
        meta: {
            layout: 'error',
            title: 'الخدمة غير متاحة',
        },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'error-404',
        component: () => import('@/pages/errors/404.vue'),
        meta: {
            layout: 'error',
            title: 'الصفحة غير موجودة',
        },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

setupGuards(router);

export default router;
