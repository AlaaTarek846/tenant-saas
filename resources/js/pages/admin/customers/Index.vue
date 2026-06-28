<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import { ADMIN_API_ROUTES, CUSTOMER_STATUSES, PERMISSIONS } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_CUSTOMER));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_CUSTOMER));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_CUSTOMER));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.CUSTOMERS, { resourceLabel: 'العميل' });

const search = ref('');
const statusFilter = ref('');
const page = ref(1);
const showForm = ref(false);
const editingId = ref(null);

const totalCustomers = computed(() => pagination.value?.total ?? 0);
const activeSubscribers = computed(() => pagination.value?.active_subscribers_count ?? 0);
const tableColumns = computed(() => (canManage.value ? 8 : 7));

const emptyForm = () => ({ name: '', email: '', phone: '', address: '', status: 'active' });
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function statusLabel(status) {
    return CUSTOMER_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function activeSubscriptionsCount(item) {
    return item.active_subscriptions_count ?? 0;
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

function onStatusChange() {
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
    Object.assign(form, { name: item.name, email: item.email ?? '', phone: item.phone ?? '', address: item.address ?? '', status: item.status });
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
        if (editingId.value) await update(editingId.value, { ...form });
        else await create({ ...form });
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
                            <h4 class="card-title mb-1">العملاء</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة عملاء الشركة</span>
                                <span class="badge bg-primary rounded-pill">{{ totalCustomers }} عميل</span>
                                <span class="badge bg-success rounded-pill">{{ activeSubscribers }} مشترك نشط</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة عميل
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث بالاسم أو البريد..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onStatusChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in CUSTOMER_STATUSES" :key="status.value" :value="status.value">
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
                                    <th>#</th><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>اشتراكات نشطة</th><th>تاريخ الإنشاء</th><th>الحالة</th>
                                    <th v-if="canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColumns" />
                                <tr v-else-if="!items.length"><td :colspan="tableColumns" class="text-center py-4 text-muted">لا يوجد عملاء</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.name }}</td>
                                    <td>{{ item.email ?? '—' }}</td>
                                    <td>{{ item.phone ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ activeSubscriptionsCount(item) }} اشتراك</span>
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
                            — {{ totalCustomers }} عميل
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
                    <h5 class="modal-title">{{ editingId ? 'تعديل عميل' : 'إضافة عميل' }}</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم</label>
                            <input v-model="form.name" type="text" class="form-control" maxlength="30" required>
                            <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">
                                مطلوب — اسم العميل كما يظهر في الفواتير والاشتراكات (حتى 30 حرف)
                            </p>
                            <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input v-model="form.email" type="email" class="form-control" maxlength="255">
                            <p v-if="!fieldError('email')" class="text-muted small mt-1 mb-0">
                                اختياري — بريد للتواصل وإرسال الفواتير
                            </p>
                            <div v-if="fieldError('email')" class="text-danger small mt-1">{{ fieldError('email') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الهاتف</label>
                            <input v-model="form.phone" type="text" class="form-control" maxlength="15">
                            <p v-if="!fieldError('phone')" class="text-muted small mt-1 mb-0">
                                اختياري — رقم الموبايل (حتى 15 رقم)
                            </p>
                            <div v-if="fieldError('phone')" class="text-danger small mt-1">{{ fieldError('phone') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحالة</label>
                            <select v-model="form.status" class="form-select">
                                <option v-for="s in CUSTOMER_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                «نشط» يسمح بربط اشتراكات وفواتير جديدة — «غير نشط» للأرشفة
                            </p>
                            <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">العنوان</label>
                            <textarea v-model="form.address" class="form-control" rows="2"></textarea>
                            <p v-if="!fieldError('address')" class="text-muted small mt-1 mb-0">
                                اختياري — عنوان الفوترة أو مقر العميل
                            </p>
                            <div v-if="fieldError('address')" class="text-danger small mt-1">{{ fieldError('address') }}</div>
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
