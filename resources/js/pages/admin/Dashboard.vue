<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/composables/useSkoteAssets';
import InitialsAvatar from '@/components/admin/InitialsAvatar.vue';
import { userAvatarUrl } from '@/utils/userAvatar';
import { getNameInitials } from '@/utils/initials';
import {
    ADMIN_API_ROUTES,
    INVOICE_STATUSES,
    PAYMENT_STATUSES,
    PERMISSIONS,
    ROLES,
    USER_STATUSES,
} from '@/utils/constants';
import { unwrapApiData } from '@/utils/apiResponse';
import StatCardsSkeleton from '@/components/admin/StatCardsSkeleton.vue';
import ListSkeleton from '@/components/admin/ListSkeleton.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(true);
const stats = ref({});
const recentUsers = ref([]);
const recentTenants = ref([]);
const recentCustomers = ref([]);
const recentInvoices = ref([]);
const recentPayments = ref([]);

const user = computed(() => authStore.user);
const roles = computed(() => authStore.userRoles);
const company = computed(() => user.value?.company ?? null);
const hasCompany = computed(() => Boolean(company.value));
const companyLogoUrl = computed(() => company.value?.logo_url ?? null);
const companyName = computed(() => company.value?.name ?? '');
const isSuperAdmin = computed(() => stats.value.is_super_admin ?? authStore.hasRole(ROLES.SUPER_ADMIN));
const isPlatformView = computed(() => stats.value.is_platform_view ?? (isSuperAdmin.value && !hasCompany.value));

const canCreateUser = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_USER));
const canReadUsers = computed(() => authStore.hasPermission(PERMISSIONS.READ_USER));
const canManageRoles = computed(() => authStore.hasPermission(PERMISSIONS.READ_ROLE));
const canReadCustomers = computed(() => authStore.hasPermission(PERMISSIONS.READ_CUSTOMER));
const canReadPlans = computed(() => authStore.hasPermission(PERMISSIONS.READ_SUBSCRIPTION_PLAN));
const canReadSubscriptions = computed(() => authStore.hasPermission(PERMISSIONS.READ_SUBSCRIPTION));
const canReadInvoices = computed(() => authStore.hasPermission(PERMISSIONS.READ_INVOICE));
const canReadPayments = computed(() => authStore.hasPermission(PERMISSIONS.READ_PAYMENT));
const canReadAccounts = computed(() => authStore.hasPermission(PERMISSIONS.READ_ACCOUNT));
const canReadTenants = computed(() => authStore.hasRole(ROLES.SUPER_ADMIN));

function formatMoney(value) {
    return Number(value ?? 0).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function userStatusLabel(status) {
    return USER_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function invoiceStatusLabel(status) {
    return INVOICE_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function paymentStatusLabel(status) {
    return PAYMENT_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function tenantStatusLabel(status) {
    const labels = {
        active: 'نشط',
        inactive: 'غير نشط',
        suspended: 'موقوف',
    };

    return labels[status] ?? status;
}

function tenantStatusClass(status) {
    if (status === 'active') {
        return 'badge bg-success';
    }

    if (status === 'suspended') {
        return 'badge bg-danger';
    }

    return 'badge bg-secondary';
}

function goTo(name) {
    router.push({ name });
}

const operationCards = computed(() => {
    if (isPlatformView.value) {
        return [
            {
                label: 'الشركات',
                value: stats.value.tenants_count ?? 0,
                icon: 'bx-buildings',
                route: 'admin.tenants',
                hint: `${stats.value.active_tenants_count ?? 0} نشطة`,
            },
            {
                label: 'المستخدمون',
                value: stats.value.users_count ?? 0,
                icon: 'bx-user',
                route: 'admin.users',
                show: canReadUsers.value,
            },
            {
                label: 'اشتراكات نشطة',
                value: stats.value.active_subscriptions_count ?? 0,
                icon: 'bx-repeat',
                route: 'admin.subscriptions',
                show: canReadSubscriptions.value,
                hint: `${stats.value.subscriptions_count ?? 0} إجمالي`,
            },
            {
                label: 'فواتير معلقة',
                value: stats.value.pending_invoices_count ?? 0,
                icon: 'bx-receipt',
                route: 'admin.invoices',
                show: canReadInvoices.value,
                hint: formatMoney(stats.value.pending_invoices_total),
            },
            {
                label: 'إجمالي التحصيل',
                value: formatMoney(stats.value.payments_total),
                icon: 'bx-money',
                route: 'admin.payments',
                show: canReadPayments.value,
                isMoney: true,
                hint: `${stats.value.payments_count ?? 0} عملية`,
            },
        ].filter((card) => card.show !== false);
    }

    return [
        { label: 'العملاء', value: stats.value.customers_count ?? 0, icon: 'bx-user-pin', route: 'admin.customers', show: canReadCustomers.value },
        { label: 'خطط الاشتراك', value: stats.value.subscription_plans_count ?? 0, icon: 'bx-package', route: 'admin.subscription-plans', show: canReadPlans.value },
        { label: 'اشتراكات نشطة', value: stats.value.active_subscriptions_count ?? 0, icon: 'bx-repeat', route: 'admin.subscriptions', show: canReadSubscriptions.value },
        { label: 'فواتير معلقة', value: stats.value.pending_invoices_count ?? 0, icon: 'bx-receipt', route: 'admin.invoices', show: canReadInvoices.value, hint: formatMoney(stats.value.pending_invoices_total) },
        { label: 'فواتير مدفوعة', value: stats.value.paid_invoices_count ?? 0, icon: 'bx-check-circle', route: 'admin.invoices', show: canReadInvoices.value },
        { label: 'المدفوعات', value: stats.value.payments_count ?? 0, icon: 'bx-credit-card', route: 'admin.payments', show: canReadPayments.value, hint: formatMoney(stats.value.payments_total) },
    ].filter((card) => card.show);
});

const financialCards = computed(() => {
    if (isPlatformView.value) {
        return [];
    }

    return [
        { label: 'نقدية (Cash)', value: formatMoney(stats.value.cash_balance), icon: 'bx-wallet', route: 'admin.balance-sheet', show: canReadAccounts.value, isMoney: true },
        { label: 'ذمم مدينة (AR)', value: formatMoney(stats.value.accounts_receivable), icon: 'bx-transfer', route: 'admin.balance-sheet', show: canReadAccounts.value, isMoney: true },
        { label: 'إيرادات مؤجلة', value: formatMoney(stats.value.deferred_revenue), icon: 'bx-time-five', route: 'admin.balance-sheet', show: canReadAccounts.value, isMoney: true },
        { label: 'إيرادات الشهر', value: formatMoney(stats.value.subscription_revenue_mtd), icon: 'bx-line-chart', route: 'admin.income-statement', show: canReadAccounts.value, isMoney: true },
        { label: 'القيود المحاسبية', value: stats.value.journal_entries_count ?? 0, icon: 'bx-notepad', route: 'admin.journal-entries', show: canReadAccounts.value },
    ].filter((card) => card.show);
});

async function loadDashboard() {
    loading.value = true;

    try {
        const response = await api.get(ADMIN_API_ROUTES.DASHBOARD);
        const data = unwrapApiData(response);

        stats.value = data?.stats ?? {};
        recentUsers.value = data?.recent_users ?? [];
        recentTenants.value = data?.recent_tenants ?? [];
        recentCustomers.value = data?.recent_customers ?? [];
        recentInvoices.value = data?.recent_invoices ?? [];
        recentPayments.value = data?.recent_payments ?? [];
    } catch {
        stats.value = {};
        recentUsers.value = [];
        recentTenants.value = [];
        recentCustomers.value = [];
        recentInvoices.value = [];
        recentPayments.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await authStore.fetchUser();
    await loadDashboard();
});
</script>

<template>
    <div class="row">
        <div class="col-xl-4">
            <div class="card overflow-hidden">
                <div class="bg-primary bg-soft">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-3">
                                <h5 class="text-primary">لوحة الإدارة</h5>
                                <p class="mb-0">{{ isPlatformView ? 'إدارة المنصة — Super Admin' : `مرحباً${user?.name ? `، ${user.name}` : ''}` }}</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img v-if="hasCompany && companyLogoUrl" :src="companyLogoUrl" alt="" class="img-fluid company-banner-logo">
                            <div v-else-if="hasCompany" class="company-banner-initials">{{ getNameInitials(companyName) }}</div>
                            <img v-else :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="avatar-md profile-user-wid mb-4">
                                <InitialsAvatar
                                    :name="hasCompany ? companyName : (user?.name ?? '')"
                                    :image-url="hasCompany ? companyLogoUrl : userAvatarUrl(user)"
                                    :shape="hasCompany ? 'rounded' : 'circle'"
                                    :size="78"
                                    font-size="1.25rem"
                                />
                            </div>
                            <h5 class="font-size-15 text-truncate">{{ user?.name ?? 'Admin' }}</h5>
                            <p v-if="hasCompany" class="text-primary mb-1 text-truncate fw-medium">{{ companyName }}</p>
                            <p class="text-muted mb-0 text-truncate">{{ roles.join(', ') || '—' }}</p>
                        </div>
                        <div class="col-sm-8">
                            <div class="pt-4 d-grid gap-2">
                                <button v-if="canReadTenants" type="button" class="btn btn-primary btn-sm" @click="goTo('admin.tenants')">
                                    <i class="bx bx-buildings me-1"></i> الشركات
                                </button>
                                <button v-if="canReadUsers" type="button" class="btn btn-primary btn-sm" @click="goTo('admin.users')">
                                    <i class="bx bx-user me-1"></i> المستخدمون
                                </button>
                                <button v-if="canManageRoles && isPlatformView" type="button" class="btn btn-soft-primary btn-sm" @click="goTo('admin.roles')">
                                    <i class="bx bx-shield-quarter me-1"></i> الأدوار والصلاحيات
                                </button>
                                <template v-if="!isPlatformView">
                                    <button v-if="canReadCustomers" type="button" class="btn btn-primary btn-sm" @click="goTo('admin.customers')">
                                        <i class="bx bx-user-pin me-1"></i> العملاء
                                    </button>
                                    <button v-if="canReadSubscriptions" type="button" class="btn btn-soft-primary btn-sm" @click="goTo('admin.subscriptions')">
                                        <i class="bx bx-repeat me-1"></i> الاشتراكات
                                    </button>
                                    <button v-if="canReadInvoices" type="button" class="btn btn-soft-primary btn-sm" @click="goTo('admin.invoices')">
                                        <i class="bx bx-receipt me-1"></i> الفواتير
                                    </button>
                                    <button v-if="authStore.hasPermission(PERMISSIONS.UPDATE_INVOICE)" type="button" class="btn btn-soft-success btn-sm" @click="goTo('admin.billing')">
                                        <i class="bx bx-calendar-check me-1"></i> عمليات نهاية الشهر
                                    </button>
                                    <button v-if="canReadAccounts" type="button" class="btn btn-soft-primary btn-sm" @click="goTo('admin.income-statement')">
                                        <i class="bx bx-bar-chart-alt-2 me-1"></i> التقارير المالية
                                    </button>
                                </template>
                                <button v-if="canCreateUser && !isPlatformView" type="button" class="btn btn-soft-primary btn-sm" @click="goTo('admin.users')">
                                    <i class="bx bx-user-plus me-1"></i> إضافة مستخدم
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <template v-if="loading">
                <span class="placeholder placeholder-wave col-3 rounded mb-3 d-block section-title-skeleton"></span>
                <StatCardsSkeleton :count="6" />
                <template v-if="hasCompany">
                    <span class="placeholder placeholder-wave col-3 rounded mb-3 mt-2 d-block section-title-skeleton"></span>
                    <StatCardsSkeleton :count="3" />
                </template>
            </template>

            <template v-else>
                <h5 class="font-size-14 text-muted mb-3">{{ isPlatformView ? 'نظرة عامة على المنصة' : 'العمليات' }}</h5>
                <div class="row">
                    <div v-for="card in operationCards" :key="card.label" class="col-md-4 col-sm-6">
                        <div class="card mini-stats-wid cursor-pointer" role="button" @click="goTo(card.route)">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="text-muted fw-medium mb-1">{{ card.label }}</p>
                                        <h4 class="mb-0" :class="{ 'font-size-14': card.isMoney }">{{ card.value }}</h4>
                                        <p v-if="card.hint" class="text-muted small mb-0 mt-1">{{ card.hint }}</p>
                                    </div>
                                    <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                                        <span class="avatar-title rounded-circle bg-primary">
                                            <i :class="`bx ${card.icon} font-size-24`"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <template v-if="financialCards.length">
                    <h5 class="font-size-14 text-muted mb-3 mt-2">المالية والمحاسبة</h5>
                    <div class="row">
                        <div v-for="card in financialCards" :key="card.label" class="col-md-4 col-sm-6">
                            <div class="card mini-stats-wid cursor-pointer border-success border-opacity-25" role="button" @click="goTo(card.route)">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <p class="text-muted fw-medium mb-1">{{ card.label }}</p>
                                            <h4 class="mb-0 font-size-14">{{ card.value }}</h4>
                                        </div>
                                        <div class="avatar-sm rounded-circle bg-success align-self-center mini-stat-icon">
                                            <span class="avatar-title rounded-circle bg-success">
                                                <i :class="`bx ${card.icon} font-size-24`"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <div class="row">
                <div v-if="isPlatformView && canReadTenants" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">آخر الشركات المسجّلة</h4>
                                <button type="button" class="btn btn-sm btn-primary" @click="goTo('admin.tenants')">عرض الكل</button>
                            </div>
                            <div v-if="loading" class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الشركة</th>
                                            <th>صاحب الشركة</th>
                                            <th>المستخدمون</th>
                                            <th>العملاء</th>
                                            <th>اشتراكات نشطة</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <TableSkeleton :rows="5" :columns="6" />
                                </table>
                            </div>
                            <div v-else-if="!recentTenants.length" class="text-center py-3 text-muted">لا توجد شركات</div>
                            <div v-else class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الشركة</th>
                                            <th>صاحب الشركة</th>
                                            <th>المستخدمون</th>
                                            <th>العملاء</th>
                                            <th>اشتراكات نشطة</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in recentTenants" :key="item.id">
                                            <td>
                                                <span class="d-block fw-medium">{{ item.name }}</span>
                                                <span class="text-muted small">{{ item.email ?? '—' }}</span>
                                            </td>
                                            <td>
                                                <span v-if="item.owner">{{ item.owner.name }}</span>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                            <td>{{ item.users_count ?? 0 }}</td>
                                            <td>{{ item.customers_count ?? 0 }}</td>
                                            <td>{{ item.active_subscriptions_count ?? 0 }}</td>
                                            <td>
                                                <span :class="tenantStatusClass(item.status)">{{ tenantStatusLabel(item.status) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <template v-if="!isPlatformView">
                <div v-if="canReadCustomers" class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">آخر العملاء</h4>
                                <button type="button" class="btn btn-sm btn-primary" @click="goTo('admin.customers')">عرض الكل</button>
                            </div>
                            <ListSkeleton v-if="loading" :rows="5" />
                            <div v-else-if="!recentCustomers.length" class="text-center py-3 text-muted">لا يوجد عملاء</div>
                            <ul v-else class="list-group list-group-flush">
                                <li v-for="item in recentCustomers" :key="item.id" class="list-group-item px-0 d-flex justify-content-between">
                                    <span>{{ item.name }}</span>
                                    <span class="text-muted small">{{ item.email ?? '—' }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-if="canReadInvoices" class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">آخر الفواتير</h4>
                                <button type="button" class="btn btn-sm btn-primary" @click="goTo('admin.invoices')">عرض الكل</button>
                            </div>
                            <ListSkeleton v-if="loading" :rows="5" />
                            <div v-else-if="!recentInvoices.length" class="text-center py-3 text-muted">لا توجد فواتير</div>
                            <ul v-else class="list-group list-group-flush">
                                <li v-for="item in recentInvoices" :key="item.id" class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="d-block">{{ item.invoice_number }}</span>
                                        <span class="text-muted small">{{ item.customer?.name ?? '—' }}</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="d-block">{{ formatMoney(item.total) }}</span>
                                        <span class="badge bg-soft-primary">{{ invoiceStatusLabel(item.status) }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                </template>
            </div>

            <div v-if="!isPlatformView && canReadPayments" class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title mb-0">آخر المدفوعات</h4>
                                <button type="button" class="btn btn-sm btn-primary" @click="goTo('admin.payments')">عرض الكل</button>
                            </div>
                            <div v-if="loading" class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الفاتورة</th>
                                            <th>العميل</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <TableSkeleton :rows="5" :columns="4" />
                                </table>
                            </div>
                            <div v-else-if="!recentPayments.length" class="text-center py-3 text-muted">لا توجد مدفوعات</div>
                            <div v-else class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الفاتورة</th>
                                            <th>العميل</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in recentPayments" :key="item.id">
                                            <td>{{ item.invoice?.invoice_number ?? `#${item.invoice_id}` }}</td>
                                            <td>{{ item.invoice?.customer?.name ?? '—' }}</td>
                                            <td>{{ formatMoney(item.amount) }}</td>
                                            <td>{{ paymentStatusLabel(item.status) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="canReadUsers" class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">آخر المستخدمين</h4>
                        <button type="button" class="btn btn-sm btn-primary" @click="goTo('admin.users')">عرض الكل</button>
                    </div>
                    <div v-if="loading" class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr><th>الاسم</th><th>البريد</th><th>الحالة</th><th>الأدوار</th></tr>
                            </thead>
                            <TableSkeleton :rows="5" :columns="4" />
                        </table>
                    </div>
                    <div v-else-if="!recentUsers.length" class="text-center py-4 text-muted">لا يوجد مستخدمون</div>
                    <div v-else class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr><th>الاسم</th><th>البريد</th><th>الحالة</th><th>الأدوار</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in recentUsers" :key="item.id">
                                    <td>{{ item.name }}</td>
                                    <td>{{ item.email }}</td>
                                    <td>{{ userStatusLabel(item.status) }}</td>
                                    <td>{{ (item.roles ?? []).join(', ') || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.company-banner-logo { max-height: 100px; object-fit: contain; padding: 0.5rem; }
.company-banner-initials { color: #556ee6; font-size: 1.75rem; font-weight: 700; text-align: center; padding: 1.25rem 0.5rem 1.5rem; line-height: 1; }
.cursor-pointer { cursor: pointer; }
.section-title-skeleton {
    min-height: 0.85rem;
    opacity: 0.65;
}
</style>
