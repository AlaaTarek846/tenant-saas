<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import { ADMIN_API_ROUTES, BILLING_CYCLES, PERMISSIONS, SUBSCRIPTION_PLAN_STATUSES } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_SUBSCRIPTION_PLAN));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_SUBSCRIPTION_PLAN));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_SUBSCRIPTION_PLAN));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.SUBSCRIPTION_PLANS, { resourceLabel: 'خطة الاشتراك' });

const search = ref('');
const statusFilter = ref('');
const page = ref(1);
const showForm = ref(false);
const editingId = ref(null);

const totalPlans = computed(() => pagination.value?.total ?? 0);
const totalSubscribedCustomers = computed(() => pagination.value?.subscribed_customers_count ?? 0);
const tableColumns = computed(() => (canManage.value ? 10 : 9));

const emptyFeature = () => ({ feature: '', value: '' });
const emptyForm = () => ({
    name: '',
    description: '',
    price: '',
    billing_cycle: 'monthly',
    currency: 'USD',
    status: 'active',
    features: [emptyFeature()],
});
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function statusLabel(status) {
    return SUBSCRIPTION_PLAN_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function billingCycleLabel(cycle) {
    return BILLING_CYCLES.find((item) => item.value === cycle)?.label ?? cycle;
}

function formatPrice(price, currency) {
    if (price === null || price === undefined || price === '') return '—';
    return `${Number(price).toLocaleString('ar-EG')} ${currency ?? ''}`.trim();
}

function featuresCount(item) {
    return item.features_count ?? item.features?.length ?? 0;
}

function subscribedCustomersCount(item) {
    return item.active_subscriptions_count ?? item.subscriptions_count ?? 0;
}

function normalizeFeatures(features = []) {
    const rows = features.map((item) => ({
        feature: item.feature ?? '',
        value: item.value ?? '',
    }));

    return rows.length ? rows : [emptyFeature()];
}

function addFeature() {
    form.features.push(emptyFeature());
}

function removeFeature(index) {
    if (form.features.length === 1) {
        form.features.splice(0, 1, emptyFeature());
        return;
    }

    form.features.splice(index, 1);
}

function payload() {
    return {
        ...form,
        features: form.features
            .filter((item) => item.feature)
            .map((item) => ({ feature: item.feature, value: item.value || null })),
    };
}

async function loadItems() {
    await fetchList({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
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
        name: item.name,
        description: item.description ?? '',
        price: item.price ?? '',
        billing_cycle: item.billing_cycle ?? 'monthly',
        currency: item.currency ?? 'USD',
        status: item.status ?? 'active',
        features: normalizeFeatures(item.features ?? []),
    });
    showForm.value = true;
}

async function handleDelete(item) {
    const confirmed = await removeConfirmed(item.id, { label: item.name });

    if (confirmed) {
        await loadItems();
    }
}

async function submitForm() {
    try {
        if (editingId.value) await update(editingId.value, payload());
        else await create(payload());
        showForm.value = false;
        await loadItems();
    } catch { /* errors in composable */ }
}

onMounted(loadItems);
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">خطط الاشتراك</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة باقات وأسعار الاشتراكات</span>
                                <span class="badge bg-primary rounded-pill">{{ totalPlans }} خطة</span>
                                <span class="badge bg-success rounded-pill">{{ totalSubscribedCustomers }} عميل مشترك</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة خطة
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث باسم الخطة..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onFilterChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in SUBSCRIPTION_PLAN_STATUSES" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div v-if="errorMessage" class="alert alert-danger mt-3">{{ errorMessage }}</div>
                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>الاسم</th><th>السعر</th><th>الدورة</th><th>العملة</th><th>المميزات</th><th>العملاء المشتركون</th><th>تاريخ الإنشاء</th><th>الحالة</th>
                                    <th v-if="canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColumns" />
                                <tr v-else-if="!items.length"><td :colspan="tableColumns" class="text-center py-4 text-muted">لا توجد خطط اشتراك</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ item.name }}</div>
                                        <small class="text-muted">{{ item.description ?? '—' }}</small>
                                    </td>
                                    <td>{{ formatPrice(item.price, item.currency) }}</td>
                                    <td>{{ billingCycleLabel(item.billing_cycle) }}</td>
                                    <td>{{ item.currency ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ featuresCount(item) }} ميزة</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ subscribedCustomersCount(item) }} عميل</span>
                                    </td>
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
                            — {{ totalPlans }} خطة
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
                    <h5 class="modal-title">{{ editingId ? 'تعديل خطة اشتراك' : 'إضافة خطة اشتراك' }}</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الخطة</label>
                            <input v-model="form.name" type="text" class="form-control" maxlength="255" required>
                            <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">
                                مطلوب — اسم يظهر عند اختيار الخطة (حتى 255 حرف)
                            </p>
                            <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">السعر</label>
                            <input v-model="form.price" type="number" min="0" step="0.01" class="form-control" required>
                            <p v-if="!fieldError('price')" class="text-muted small mt-1 mb-0">
                                مطلوب — سعر الاشتراك لكل دورة فوترة (0 أو أكثر)
                            </p>
                            <div v-if="fieldError('price')" class="text-danger small mt-1">{{ fieldError('price') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">دورة الفوترة</label>
                            <select v-model="form.billing_cycle" class="form-select" required>
                                <option v-for="cycle in BILLING_CYCLES" :key="cycle.value" :value="cycle.value">{{ cycle.label }}</option>
                            </select>
                            <p v-if="!fieldError('billing_cycle')" class="text-muted small mt-1 mb-0">
                                مطلوب — شهري أو سنوي لتحديد موعد الفوترة التالية
                            </p>
                            <div v-if="fieldError('billing_cycle')" class="text-danger small mt-1">{{ fieldError('billing_cycle') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">العملة</label>
                            <input v-model="form.currency" type="text" maxlength="3" class="form-control text-uppercase" required>
                            <p v-if="!fieldError('currency')" class="text-muted small mt-1 mb-0">
                                مطلوب — رمز ISO من 3 أحرف (مثل USD)
                            </p>
                            <div v-if="fieldError('currency')" class="text-danger small mt-1">{{ fieldError('currency') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select v-model="form.status" class="form-select">
                                <option v-for="s in SUBSCRIPTION_PLAN_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                «نشط» متاح للاشتراكات الجديدة — «غير نشط» للأرشفة
                            </p>
                            <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف</label>
                            <textarea v-model="form.description" class="form-control" rows="2"></textarea>
                            <p v-if="!fieldError('description')" class="text-muted small mt-1 mb-0">
                                اختياري — وصف مختصر يظهر في قائمة الخطط
                            </p>
                            <div v-if="fieldError('description')" class="text-danger small mt-1">{{ fieldError('description') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">المميزات</label>
                                <button type="button" class="btn btn-sm btn-soft-primary" @click="addFeature">
                                    <i class="bx bx-plus me-1"></i> إضافة ميزة
                                </button>
                            </div>
                            <p class="text-muted small mb-2">اختياري — قائمة مميزات الخطة (اسم الميزة + قيمتها)</p>
                            <div v-for="(feature, index) in form.features" :key="index" class="row g-2 align-items-start mb-2">
                                <div class="col-md-5">
                                    <input v-model="feature.feature" type="text" class="form-control" placeholder="الميزة">
                                    <div v-if="fieldError(`features.${index}.feature`)" class="text-danger small">{{ fieldError(`features.${index}.feature`) }}</div>
                                </div>
                                <div class="col-md-5">
                                    <input v-model="feature.value" type="text" class="form-control" placeholder="القيمة">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-soft-danger w-100" @click="removeFeature(index)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
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
