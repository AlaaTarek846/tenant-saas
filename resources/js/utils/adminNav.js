import { PERMISSIONS, ROLES } from '@/utils/constants';

export function buildAdminNavItems(authStore) {
    const items = [];

    items.push({
        name: 'admin.dashboard',
        label: 'لوحة الإدارة',
        icon: 'bx bx-home-circle',
    });

    if (authStore.hasRole(ROLES.SUPER_ADMIN)) {
        items.push({
            name: 'admin.tenants',
            label: 'المستأجرون',
            icon: 'bx bx-buildings',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_CUSTOMER)) {
        items.push({
            name: 'admin.customers',
            label: 'العملاء',
            icon: 'bx bx-user-pin',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_SUBSCRIPTION_PLAN)) {
        items.push({
            name: 'admin.subscription-plans',
            label: 'خطط الاشتراك',
            icon: 'bx bx-package',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_SUBSCRIPTION)) {
        items.push({
            name: 'admin.subscriptions',
            label: 'الاشتراكات',
            icon: 'bx bx-repeat',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_INVOICE)) {
        items.push({
            name: 'admin.invoices',
            label: 'الفواتير',
            icon: 'bx bx-receipt',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.UPDATE_INVOICE)) {
        items.push({
            name: 'admin.billing',
            label: 'عمليات نهاية الشهر',
            icon: 'bx bx-calendar-check',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_PAYMENT)) {
        items.push({
            name: 'admin.payments',
            label: 'المدفوعات',
            icon: 'bx bx-money',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_ACCOUNT)) {
        items.push({
            name: 'admin.accounts',
            label: 'دليل الحسابات',
            icon: 'bx bx-book-bookmark',
        });
        items.push({
            name: 'admin.journal-entries',
            label: 'القيود المحاسبية',
            icon: 'bx bx-notepad',
        });
        items.push({
            name: 'admin.income-statement',
            label: 'قائمة الدخل',
            icon: 'bx bx-line-chart',
        });
        items.push({
            name: 'admin.balance-sheet',
            label: 'الميزانية العمومية',
            icon: 'bx bx-pie-chart-alt-2',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_USER)) {
        items.push({
            name: 'admin.users',
            label: 'المستخدمون',
            icon: 'bx bx-user',
        });
    }

    if (authStore.hasPermission(PERMISSIONS.READ_ROLE)) {
        items.push({
            name: 'admin.roles',
            label: 'الأدوار والصلاحيات',
            icon: 'bx bx-shield-quarter',
        });
    }

    return items;
}
