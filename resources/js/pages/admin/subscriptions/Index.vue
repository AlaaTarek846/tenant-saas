<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, PERMISSIONS, SUBSCRIPTION_STATUSES } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_SUBSCRIPTION));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_SUBSCRIPTION));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_SUBSCRIPTION));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.SUBSCRIPTIONS, { resourceLabel: 'الاشتراك' });

const customerOptions = ref([]);
const planOptions = ref([]);
const optionsLoading = ref(false);
const search = ref('');
const statusFilter = ref('');
const startDateFilter = ref('');
const nextBillingDateFilter = ref('');
const page = ref(1);
const showForm = ref(false);
const editingId = ref(null);

const totalSubscriptions = computed(() => pagination.value?.total ?? 0);
const tableColumns = computed(() => (canManage.value ? 9 : 8));

const emptyForm = () => ({
    customer_id: '',
    subscription_plan_id: '',
    start_date: '',
    end_date: '',
    next_billing_date: '',
    status: 'active',
});
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function statusLabel(status) {
    return SUBSCRIPTION_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function customerName(item) {
    return item.customer?.name ?? customerOptions.value.find((option) => option.id === item.customer_id)?.name ?? '—';
}

function planName(item) {
    return item.subscription_plan?.name ?? planOptions.value.find((option) => option.id === item.subscription_plan_id)?.name ?? '—';
}

function invoicesCount(item) {
    return item.invoices_count ?? 0;
}

async function loadOptions() {
    optionsLoading.value = true;
    try {
        const [customersResponse, plansResponse] = await Promise.all([
            api.get(ADMIN_API_ROUTES.CUSTOMER_OPTIONS),
            api.get(ADMIN_API_ROUTES.SUBSCRIPTION_PLAN_OPTIONS),
        ]);
        customerOptions.value = unwrapApiData(customersResponse) ?? [];
        planOptions.value = unwrapApiData(plansResponse) ?? [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل خيارات الاشتراكات.';
    } finally {
        optionsLoading.value = false;
    }
}

async function loadItems() {
    await fetchList({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        start_date: startDateFilter.value || undefined,
        next_billing_date: nextBillingDateFilter.value || undefined,
        page: page.value,
        paginate: 10,
    });
}

function onSearch(value) {
    search.value = value;
    page.value = 1;
    loadItems();
}

function onFilterChange() {
    page.value = 1;
    loadItems();
}

function changePage(nextPage) {
    page.value = nextPage;
    loadItems();
}

function openCreate() {
    editingId.value = null;
    Object.assign(form, emptyForm());
    showForm.value = true;
}

function openEdit(item) {
    editingId.value = item.id;
    Object.assign(form, {
        customer_id: item.customer_id ?? '',
        subscription_plan_id: item.subscription_plan_id ?? '',
        start_date: item.start_date ?? '',
        end_date: item.end_date ?? '',
        next_billing_date: item.next_billing_date ?? '',
        status: item.status ?? 'active',
    });
    showForm.value = true;
}

async function handleDelete(item) {
    const confirmed = await removeConfirmed(item.id, {
        message: `هل تريد حذف اشتراك ${customerName(item)}؟`,
    });

    if (confirmed) {
        await loadItems();
    }
}

async function submitForm() {
    try {
        const payload = { ...form, end_date: form.end_date || null };
        if (editingId.value) await update(editingId.value, payload);
        else await create(payload);
        showForm.value = false;
        await loadItems();
    } catch { /* errors in composable */ }
}

onMounted(async () => {
    await Promise.all([loadOptions(), loadItems()]);
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">الاشتراكات</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة اشتراكات العملاء</span>
                                <span class="badge bg-primary rounded-pill">{{ totalSubscriptions }} اشتراك</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة اشتراك
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث باسم العميل..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onFilterChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in SUBSCRIPTION_STATUSES" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small text-muted mb-1">تاريخ البداية</label>
                            <input v-model="startDateFilter" type="date" class="form-control" @change="onFilterChange">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small text-muted mb-1">الفوترة القادمة</label>
                            <input v-model="nextBillingDateFilter" type="date" class="form-control" @change="onFilterChange">
                        </div>
                    </div>
                    <div v-if="errorMessage" class="alert alert-danger mt-3">{{ errorMessage }}</div>
                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>العميل</th><th>الخطة</th><th>الفواتير</th><th>تاريخ البداية</th><th>الفوترة القادمة</th><th>تاريخ الإنشاء</th><th>الحالة</th>
                                    <th v-if="canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColumns" />
                                <tr v-else-if="!items.length"><td :colspan="tableColumns" class="text-center py-4 text-muted">لا توجد اشتراكات</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ customerName(item) }}</td>
                                    <td>{{ planName(item) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ invoicesCount(item) }} فاتورة</span>
                                    </td>
                                    <td>{{ formatDate(item.start_date) }}</td>
                                    <td>{{ formatDate(item.next_billing_date) }}</td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td><span class="badge bg-primary">{{ statusLabel(item.status) }}</span></td>
                                    <td v-if="canManage">
                                        <button v-if="canUpdate" type="button" class="btn btn-sm btn-soft-primary me-1" @click="openEdit(item)"><i class="bx bx-edit-alt"></i></button>
                                        <button v-if="canDelete" type="button" class="btn btn-sm btn-soft-danger" @click="handleDelete(item)"><i class="bx bx-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="pagination?.last_page > 1"
                        class="d-flex justify-content-between align-items-center mt-3"
                    >
                        <span class="text-muted">
                            صفحة {{ pagination.current_page }} من {{ pagination.last_page }}
                            — {{ totalSubscriptions }} اشتراك
                        </span>
                        <div class="btn-group">
                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                :disabled="!pagination.prev_page_url"
                                @click="changePage(pagination.current_page - 1)"
                            >
                                السابق
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                :disabled="!pagination.has_more_pages"
                                @click="changePage(pagination.current_page + 1)"
                            >
                                التالي
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :class="{ show: showForm }" :style="{ display: showForm ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ editingId ? 'تعديل اشتراك' : 'إضافة اشتراك' }}</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">العميل</label>
                            <select v-model="form.customer_id" class="form-select" :disabled="optionsLoading" required>
                                <option value="">اختر العميل</option>
                                <option v-for="customer in customerOptions" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
                            </select>
                            <p v-if="!fieldError('customer_id')" class="text-muted small mt-1 mb-0">
                                مطلوب — العميل صاحب الاشتراك
                            </p>
                            <div v-if="fieldError('customer_id')" class="text-danger small mt-1">{{ fieldError('customer_id') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">خطة الاشتراك</label>
                            <select v-model="form.subscription_plan_id" class="form-select" :disabled="optionsLoading" required>
                                <option value="">اختر الخطة</option>
                                <option v-for="plan in planOptions" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                            <p v-if="!fieldError('subscription_plan_id')" class="text-muted small mt-1 mb-0">
                                مطلوب — تحدد السعر ودورة الفوترة
                            </p>
                            <div v-if="fieldError('subscription_plan_id')" class="text-danger small mt-1">{{ fieldError('subscription_plan_id') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ البداية</label>
                            <input v-model="form.start_date" type="date" class="form-control" required>
                            <p v-if="!fieldError('start_date')" class="text-muted small mt-1 mb-0">
                                مطلوب — تاريخ بدء الاشتراك
                            </p>
                            <div v-if="fieldError('start_date')" class="text-danger small mt-1">{{ fieldError('start_date') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ النهاية</label>
                            <input v-model="form.end_date" type="date" class="form-control">
                            <p v-if="!fieldError('end_date')" class="text-muted small mt-1 mb-0">
                                اختياري — اتركه فارغاً للاشتراك المفتوح
                            </p>
                            <div v-if="fieldError('end_date')" class="text-danger small mt-1">{{ fieldError('end_date') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الفوترة القادمة</label>
                            <input v-model="form.next_billing_date" type="date" class="form-control" required>
                            <p v-if="!fieldError('next_billing_date')" class="text-muted small mt-1 mb-0">
                                مطلوب — تُنشأ الفاتورة التالية عندما يحين هذا التاريخ
                            </p>
                            <div v-if="fieldError('next_billing_date')" class="text-danger small mt-1">{{ fieldError('next_billing_date') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحالة</label>
                            <select v-model="form.status" class="form-select">
                                <option v-for="s in SUBSCRIPTION_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                «نشط» يُفوَّت تلقائياً — «ملغي/موقوف» يوقف الفوترة
                            </p>
                            <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showForm = false">إلغاء</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'جاري الحفظ...' : 'حفظ' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="showForm" class="modal-backdrop fade show"></div>
</template>
