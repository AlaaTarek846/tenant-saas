export const STORAGE_KEYS = {
    TOKEN: 'auth_token',
    USER: 'auth_user',
    AUTH_MODE: 'auth_mode',
};

export const AUTH_MODES = {
    SANCTUM: 'sanctum',
    TOKEN: 'token',
};

export const ROLES = {
    SUPER_ADMIN: 'Super_Admin',
    COMPANY_ADMIN: 'Company_Admin',
};

export const ADMIN_ROLES = [ROLES.SUPER_ADMIN, ROLES.COMPANY_ADMIN];

export const PERMISSIONS = {
    CREATE_USER: 'create_user',
    READ_USER: 'read_user',
    UPDATE_USER: 'update_user',
    DELETE_USER: 'delete_user',
    CREATE_ROLE: 'create_role',
    READ_ROLE: 'read_role',
    UPDATE_ROLE: 'update_role',
    DELETE_ROLE: 'delete_role',
    READ_TENANT: 'read_tenant',
    DELETE_TENANT: 'delete_tenant',
    CREATE_CUSTOMER: 'create_customer',
    READ_CUSTOMER: 'read_customer',
    UPDATE_CUSTOMER: 'update_customer',
    DELETE_CUSTOMER: 'delete_customer',
    CREATE_SUBSCRIPTION_PLAN: 'create_subscription_plan',
    READ_SUBSCRIPTION_PLAN: 'read_subscription_plan',
    UPDATE_SUBSCRIPTION_PLAN: 'update_subscription_plan',
    DELETE_SUBSCRIPTION_PLAN: 'delete_subscription_plan',
    CREATE_SUBSCRIPTION: 'create_subscription',
    READ_SUBSCRIPTION: 'read_subscription',
    UPDATE_SUBSCRIPTION: 'update_subscription',
    DELETE_SUBSCRIPTION: 'delete_subscription',
    CREATE_INVOICE: 'create_invoice',
    READ_INVOICE: 'read_invoice',
    UPDATE_INVOICE: 'update_invoice',
    DELETE_INVOICE: 'delete_invoice',
    CREATE_PAYMENT: 'create_payment',
    READ_PAYMENT: 'read_payment',
    UPDATE_PAYMENT: 'update_payment',
    DELETE_PAYMENT: 'delete_payment',
    CREATE_ACCOUNT: 'create_account',
    READ_ACCOUNT: 'read_account',
    UPDATE_ACCOUNT: 'update_account',
    DELETE_ACCOUNT: 'delete_account',
};

export const COMPANY_PERMISSIONS = Object.values(PERMISSIONS);

export const API_ROUTES = {
    CSRF_COOKIE: '/sanctum/csrf-cookie',
    LOGIN: '/api/login',
    REGISTER: '/api/register',
    LOGOUT: '/api/logout',
    USER: '/api/user',
    FORGOT_PASSWORD: '/api/forgot-password',
    RESET_PASSWORD: '/api/reset-password',
    VERIFY_EMAIL: '/api/email/verify',
    RESEND_VERIFICATION: '/api/email/verification-notification',
    VERIFY_CODE: '/api/verify-code',
    RESEND_VERIFY_CODE: '/api/verify-code/resend',
};

export const ADMIN_API_ROUTES = {
    DASHBOARD: '/api/admin/dashboard',
    PROFILE: '/api/admin/profile',
    PROFILE_COMPANY: '/api/admin/profile/company',
    USERS: '/api/admin/users',
    ROLES: '/api/admin/roles',
    ROLE_OPTIONS: '/api/admin/roles/options',
    PERMISSIONS: '/api/admin/permissions',
    TENANT_OPTIONS: '/api/admin/tenants/options',
    TENANTS: '/api/admin/tenants',
    CUSTOMERS: '/api/admin/customers',
    CUSTOMER_OPTIONS: '/api/admin/customers/options',
    SUBSCRIPTION_PLANS: '/api/admin/subscription-plans',
    SUBSCRIPTION_PLAN_OPTIONS: '/api/admin/subscription-plans/options',
    SUBSCRIPTIONS: '/api/admin/subscriptions',
    SUBSCRIPTION_OPTIONS: '/api/admin/subscriptions/options',
    INVOICES: '/api/admin/invoices',
    PAYMENTS: '/api/admin/payments',
    ACCOUNTS: '/api/admin/accounts',
    JOURNAL_ENTRIES: '/api/admin/journal-entries',
    BILLING_GENERATE_INVOICES: '/api/admin/billing/generate-invoices',
    BILLING_RECOGNIZE_REVENUE: '/api/admin/billing/recognize-revenue',
    REPORTS_INCOME_STATEMENT: '/api/admin/reports/income-statement',
    REPORTS_BALANCE_SHEET: '/api/admin/reports/balance-sheet',
};

export const JOURNAL_ENTRY_SOURCES = [
    { value: '', label: 'الكل' },
    { value: 'manual', label: 'يدوي' },
    { value: 'invoice', label: 'فاتورة' },
    { value: 'payment', label: 'دفعة' },
];

export const USER_STATUSES = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
    { value: 'suspended', label: 'موقوف' },
];

export const CUSTOMER_STATUSES = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

export const SUBSCRIPTION_PLAN_STATUSES = [
    { value: 'active', label: 'نشط' },
    { value: 'inactive', label: 'غير نشط' },
];

export const BILLING_CYCLES = [
    { value: 'monthly', label: 'شهري' },
    { value: 'yearly', label: 'سنوي' },
];

export const SUBSCRIPTION_STATUSES = [
    { value: 'active', label: 'نشط' },
    { value: 'paused', label: 'موقوف مؤقتاً' },
    { value: 'cancelled', label: 'ملغي' },
    { value: 'expired', label: 'منتهي' },
];

export const INVOICE_STATUSES = [
    { value: 'draft', label: 'مسودة' },
    { value: 'pending', label: 'معلقة' },
    { value: 'paid', label: 'مدفوعة' },
    { value: 'cancelled', label: 'ملغاة' },
];

export const PAYMENT_STATUSES = [
    { value: 'pending', label: 'معلق' },
    { value: 'paid', label: 'مدفوع' },
    { value: 'failed', label: 'فشل' },
    { value: 'refunded', label: 'مسترد' },
];

export const ACCOUNT_TYPES = [
    { value: 'Asset', label: 'أصول' },
    { value: 'Liability', label: 'خصوم' },
    { value: 'Revenue', label: 'إيرادات' },
    { value: 'Expense', label: 'مصروفات' },
];

/** @see database/seeders/UserSeeder.php */
export const DEMO_ACCOUNTS = {
    SUPER_ADMIN: {
        label: 'Super Admin',
        buttonTitle: 'مدير المنصة',
        email: 'superadmin@gmail.com',
        password: '123456',
        icon: 'bx-user-shield',
        variant: 'super',
    },
    COMPANY_ADMIN: {
        label: 'Company Admin',
        buttonTitle: 'مدير الشركة',
        email: 'companyadmin@gmail.com',
        password: '123456',
        icon: 'bx-buildings',
        variant: 'company',
    },
};

/** @see config/verify.php VERIFY_CODE_FIXED */
export const DEMO_VERIFY_CODE = import.meta.env.VITE_DEMO_VERIFY_CODE ?? '123456';

export const SHOW_DEMO_HELPERS = import.meta.env.DEV
    || import.meta.env.VITE_DEMO_HELPERS === 'true';
