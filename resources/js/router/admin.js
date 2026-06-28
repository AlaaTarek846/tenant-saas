import { PERMISSIONS, ROLES } from '@/utils/constants';

const subscriptionRoutes = [
    {
        path: '/admin/customers',
        name: 'admin.customers',
        component: () => import('@/pages/admin/customers/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_CUSTOMER, PERMISSIONS.CREATE_CUSTOMER],
            title: 'العملاء',
        },
    },
    {
        path: '/admin/subscription-plans',
        name: 'admin.subscription-plans',
        component: () => import('@/pages/admin/subscription-plans/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_SUBSCRIPTION_PLAN, PERMISSIONS.CREATE_SUBSCRIPTION_PLAN],
            title: 'خطط الاشتراك',
        },
    },
    {
        path: '/admin/subscriptions',
        name: 'admin.subscriptions',
        component: () => import('@/pages/admin/subscriptions/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_SUBSCRIPTION, PERMISSIONS.CREATE_SUBSCRIPTION],
            title: 'الاشتراكات',
        },
    },
    {
        path: '/admin/invoices',
        name: 'admin.invoices',
        component: () => import('@/pages/admin/invoices/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_INVOICE, PERMISSIONS.CREATE_INVOICE],
            title: 'الفواتير',
        },
    },
    {
        path: '/admin/billing',
        name: 'admin.billing',
        component: () => import('@/pages/admin/billing/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.UPDATE_INVOICE, PERMISSIONS.CREATE_INVOICE],
            title: 'عمليات نهاية الشهر',
        },
    },
    {
        path: '/admin/payments',
        name: 'admin.payments',
        component: () => import('@/pages/admin/payments/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_PAYMENT, PERMISSIONS.CREATE_PAYMENT],
            title: 'المدفوعات',
        },
    },
    {
        path: '/admin/accounts',
        name: 'admin.accounts',
        component: () => import('@/pages/admin/accounts/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_ACCOUNT, PERMISSIONS.CREATE_ACCOUNT],
            title: 'دليل الحسابات',
        },
    },
    {
        path: '/admin/journal-entries',
        name: 'admin.journal-entries',
        component: () => import('@/pages/admin/journal-entries/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_ACCOUNT, PERMISSIONS.CREATE_ACCOUNT],
            title: 'القيود المحاسبية',
        },
    },
    {
        path: '/admin/income-statement',
        name: 'admin.income-statement',
        component: () => import('@/pages/admin/income-statement/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_ACCOUNT],
            title: 'قائمة الدخل',
        },
    },
    {
        path: '/admin/balance-sheet',
        name: 'admin.balance-sheet',
        component: () => import('@/pages/admin/balance-sheet/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [PERMISSIONS.READ_ACCOUNT],
            title: 'الميزانية العمومية',
        },
    },
    {
        path: '/admin/reports',
        redirect: { name: 'admin.income-statement' },
    },
];

export const adminRoutes = [
    {
        path: '/admin',
        redirect: { name: 'admin.dashboard' },
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: () => import('@/pages/admin/Dashboard.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            title: 'لوحة الإدارة',
        },
    },
    {
        path: '/admin/profile',
        name: 'admin.profile',
        component: () => import('@/pages/admin/Profile.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            title: 'الملف الشخصي',
        },
    },
    {
        path: '/admin/tenants',
        name: 'admin.tenants',
        component: () => import('@/pages/admin/tenants/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            roles: [ROLES.SUPER_ADMIN],
            title: 'المستأجرون',
        },
    },
    ...subscriptionRoutes,
    {
        path: '/admin/users',
        name: 'admin.users',
        component: () => import('@/pages/admin/users/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [
                PERMISSIONS.READ_USER,
                PERMISSIONS.CREATE_USER,
                PERMISSIONS.UPDATE_USER,
                PERMISSIONS.DELETE_USER,
            ],
            title: 'المستخدمون',
        },
    },
    {
        path: '/admin/roles',
        name: 'admin.roles',
        component: () => import('@/pages/admin/roles/Index.vue'),
        meta: {
            layout: 'admin',
            requiresAuth: true,
            requiresAdmin: true,
            permissions: [
                PERMISSIONS.READ_ROLE,
                PERMISSIONS.CREATE_ROLE,
                PERMISSIONS.UPDATE_ROLE,
                PERMISSIONS.DELETE_ROLE,
            ],
            title: 'الأدوار والصلاحيات',
        },
    },
];
