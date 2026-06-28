<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, INVOICE_STATUSES, PERMISSIONS } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_INVOICE));
const canRead = computed(() => authStore.hasPermission(PERMISSIONS.READ_INVOICE));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_INVOICE));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_INVOICE));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, fetchOne, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.INVOICES, { resourceLabel: 'الفاتورة' });

const customerOptions = ref([]);
const subscriptionOptions = ref([]);
const planOptions = ref([]);
const optionsLoading = ref(false);
const search = ref('');
const statusFilter = ref('');
const page = ref(1);

const totalInvoices = computed(() => pagination.value?.total ?? 0);
const invoiceTableColumns = computed(() => (canRead.value || canManage.value ? 8 : 7));
const showForm = ref(false);
const showView = ref(false);
const editingId = ref(null);
const viewInvoice = ref(null);
const journalEntries = ref([]);
const detailsLoading = ref(false);

const emptyItem = () => ({
    subscription_plan_id: '',
    description: '',
    quantity: 1,
    unit_price: '',
});
const emptyForm = () => ({
    customer_id: '',
    subscription_id: '',
    issue_date: '',
    due_date: '',
    discount: 0,
    tax: 0,
    status: 'pending',
    items: [emptyItem()],
});
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function statusLabel(status) {
    return INVOICE_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function customerName(item) {
    return item.customer?.name ?? customerOptions.value.find((option) => option.id === item.customer_id)?.name ?? '—';
}

function subscriptionLabel(subscription) {
    if (!subscription) return '—';
    const customer = subscription.customer?.name ?? customerOptions.value.find((option) => option.id === subscription.customer_id)?.name;
    const plan = subscription.subscription_plan?.name ?? planOptions.value.find((option) => option.id === subscription.subscription_plan_id)?.name;
    return `#${subscription.id} - ${customer ?? 'عميل'} - ${plan ?? 'خطة'}`;
}

function formatAmount(amount) {
    if (amount === null || amount === undefined || amount === '') return '—';
    return Number(amount).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paymentsCount(item) {
    return item.payments_count ?? 0;
}

function journalEntryRows(entries = []) {
    return entries.flatMap((entry) => (entry.details ?? []).map((line) => ({
        entry_id: entry.id,
        description: entry.description,
        entry_date: entry.entry_date,
        account_code: line.account_code,
        account_name: line.account_name,
        debit: line.debit,
        credit: line.credit,
        line_description: line.description,
    })));
}

async function loadInvoiceDetails(id) {
    detailsLoading.value = true;
    try {
        const invoice = await fetchOne(id);
        journalEntries.value = invoice?.journal_entries ?? [];
        return invoice;
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل تفاصيل الفاتورة.';
        journalEntries.value = [];
        return null;
    } finally {
        detailsLoading.value = false;
    }
}

function firstItem() {
    return form.items[0];
}

function normalizeItems(invoiceItems = []) {
    const item = invoiceItems[0] ?? {};
    return [{
        subscription_plan_id: item.subscription_plan_id ?? '',
        description: item.description ?? '',
        quantity: item.quantity ?? 1,
        unit_price: item.unit_price ?? '',
    }];
}

function handlePlanChange() {
    const item = firstItem();
    const plan = planOptions.value.find((option) => option.id === item.subscription_plan_id);

    if (!plan) return;
    if (!item.description) item.description = plan.name;
    if (!item.unit_price) item.unit_price = plan.price ?? '';
}

async function loadOptions() {
    optionsLoading.value = true;
    try {
        const [customersResponse, subscriptionsResponse, plansResponse] = await Promise.all([
            api.get(ADMIN_API_ROUTES.CUSTOMER_OPTIONS),
            api.get(ADMIN_API_ROUTES.SUBSCRIPTION_OPTIONS),
            api.get(ADMIN_API_ROUTES.SUBSCRIPTION_PLAN_OPTIONS),
        ]);
        customerOptions.value = unwrapApiData(customersResponse) ?? [];
        subscriptionOptions.value = unwrapApiData(subscriptionsResponse) ?? [];
        planOptions.value = unwrapApiData(plansResponse) ?? [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل خيارات الفواتير.';
    } finally {
        optionsLoading.value = false;
    }
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
    journalEntries.value = [];
    Object.assign(form, emptyForm());
    showForm.value = true;
}

async function openView(item) {
    viewInvoice.value = item;
    showView.value = true;
    await loadInvoiceDetails(item.id);
}

async function openEdit(item) {
    editingId.value = item.id;
    Object.assign(form, {
        customer_id: item.customer_id ?? '',
        subscription_id: item.subscription_id ?? '',
        issue_date: item.issue_date ?? '',
        due_date: item.due_date ?? '',
        discount: item.discount ?? 0,
        tax: item.tax ?? 0,
        status: item.status ?? 'pending',
        items: normalizeItems(item.items ?? []),
    });
    showForm.value = true;
    await loadInvoiceDetails(item.id);
}

async function handleDelete(item) {
    const confirmed = await removeConfirmed(item.id, {
        message: `هل تريد حذف الفاتورة ${item.invoice_number ?? ''}؟`,
    });

    if (confirmed) {
        await loadItems();
    }
}

function payload() {
    return {
        ...form,
        discount: form.discount || 0,
        tax: form.tax || 0,
        items: [{
            ...firstItem(),
            quantity: firstItem().quantity || 1,
        }],
    };
}

async function submitForm() {
    try {
        let saved;
        if (editingId.value) saved = await update(editingId.value, payload());
        else saved = await create(payload());

        if (saved?.id) {
            journalEntries.value = saved.journal_entries ?? [];
            if (!showForm.value) await loadInvoiceDetails(saved.id);
        }

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
                            <h4 class="card-title mb-1">الفواتير</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة فواتير الاشتراكات</span>
                                <span class="badge bg-primary rounded-pill">{{ totalInvoices }} فاتورة</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة فاتورة
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث برقم الفاتورة أو العميل..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onStatusChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in INVOICE_STATUSES" :key="status.value" :value="status.value">
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
                                    <th>#</th><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>المدفوعات</th><th>تاريخ الإنشاء</th><th>الحالة</th>
                                    <th v-if="canRead || canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="invoiceTableColumns" />
                                <tr v-else-if="!items.length"><td :colspan="invoiceTableColumns" class="text-center py-4 text-muted">لا توجد فواتير</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.invoice_number ?? '—' }}</td>
                                    <td>{{ customerName(item) }}</td>
                                    <td>{{ formatAmount(item.total) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ paymentsCount(item) }} دفعة</span>
                                    </td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td><span class="badge bg-primary">{{ statusLabel(item.status) }}</span></td>
                                    <td v-if="canRead || canManage">
                                        <button v-if="canRead" type="button" class="btn btn-sm btn-soft-info me-1" title="عرض القيود المحاسبية" @click="openView(item)"><i class="bx bx-show"></i></button>
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
                            — {{ totalInvoices }} فاتورة
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
                    <h5 class="modal-title">{{ editingId ? 'تعديل فاتورة' : 'إضافة فاتورة' }}</h5>
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
                            <p v-if="!fieldError('customer_id')" class="text-muted small mt-1 mb-0">مطلوب — صاحب الفاتورة</p>
                            <div v-if="fieldError('customer_id')" class="text-danger small mt-1">{{ fieldError('customer_id') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاشتراك</label>
                            <select v-model="form.subscription_id" class="form-select" :disabled="optionsLoading" required>
                                <option value="">اختر الاشتراك</option>
                                <option v-for="subscription in subscriptionOptions" :key="subscription.id" :value="subscription.id">{{ subscriptionLabel(subscription) }}</option>
                            </select>
                            <p v-if="!fieldError('subscription_id')" class="text-muted small mt-1 mb-0">مطلوب — يربط الفاتورة باشتراك نشط</p>
                            <div v-if="fieldError('subscription_id')" class="text-danger small mt-1">{{ fieldError('subscription_id') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الإصدار</label>
                            <input v-model="form.issue_date" type="date" class="form-control" required>
                            <p v-if="!fieldError('issue_date')" class="text-muted small mt-1 mb-0">مطلوب — تاريخ إصدار الفاتورة</p>
                            <div v-if="fieldError('issue_date')" class="text-danger small mt-1">{{ fieldError('issue_date') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الاستحقاق</label>
                            <input v-model="form.due_date" type="date" class="form-control" required>
                            <p v-if="!fieldError('due_date')" class="text-muted small mt-1 mb-0">مطلوب — آخر موعد للسداد (بعد أو يساوي تاريخ الإصدار)</p>
                            <div v-if="fieldError('due_date')" class="text-danger small mt-1">{{ fieldError('due_date') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الخصم</label>
                            <input v-model="form.discount" type="number" min="0" step="0.01" class="form-control">
                            <p v-if="!fieldError('discount')" class="text-muted small mt-1 mb-0">اختياري — يُخصم من الإجمالي</p>
                            <div v-if="fieldError('discount')" class="text-danger small mt-1">{{ fieldError('discount') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الضريبة</label>
                            <input v-model="form.tax" type="number" min="0" step="0.01" class="form-control">
                            <p v-if="!fieldError('tax')" class="text-muted small mt-1 mb-0">اختياري — تُضاف للإجمالي</p>
                            <div v-if="fieldError('tax')" class="text-danger small mt-1">{{ fieldError('tax') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الحالة</label>
                            <select v-model="form.status" class="form-select">
                                <option v-for="s in INVOICE_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                <template v-if="form.status === 'draft'">«مسودة» لا تُنشئ قيداً محاسبياً</template>
                                <template v-else>«معلقة/مدفوعة» تُسجّل: مدين ذمم مدينة / دائن إيرادات مؤجلة</template>
                            </p>
                            <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                        </div>
                        <div class="col-12">
                            <h5 class="font-size-14 mb-3">بند الفاتورة</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">الخطة</label>
                                    <select v-model="form.items[0].subscription_plan_id" class="form-select" :disabled="optionsLoading" required @change="handlePlanChange">
                                        <option value="">اختر الخطة</option>
                                        <option v-for="plan in planOptions" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                    </select>
                                    <p v-if="!fieldError('items.0.subscription_plan_id')" class="text-muted small mt-1 mb-0">مطلوب — لملء السعر والوصف تلقائياً</p>
                                    <div v-if="fieldError('items.0.subscription_plan_id')" class="text-danger small mt-1">{{ fieldError('items.0.subscription_plan_id') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الوصف</label>
                                    <input v-model="form.items[0].description" type="text" class="form-control" maxlength="255" required>
                                    <p v-if="!fieldError('items.0.description')" class="text-muted small mt-1 mb-0">مطلوب — يظهر في تفاصيل الفاتورة (حتى 255 حرف)</p>
                                    <div v-if="fieldError('items.0.description')" class="text-danger small mt-1">{{ fieldError('items.0.description') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الكمية</label>
                                    <input v-model="form.items[0].quantity" type="number" min="1" class="form-control">
                                    <p v-if="!fieldError('items.0.quantity')" class="text-muted small mt-1 mb-0">اختياري — افتراضي 1</p>
                                    <div v-if="fieldError('items.0.quantity')" class="text-danger small mt-1">{{ fieldError('items.0.quantity') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">سعر الوحدة</label>
                                    <input v-model="form.items[0].unit_price" type="number" min="0" step="0.01" class="form-control" required>
                                    <p v-if="!fieldError('items.0.unit_price')" class="text-muted small mt-1 mb-0">مطلوب — سعر البند قبل الخصم والضريبة</p>
                                    <div v-if="fieldError('items.0.unit_price')" class="text-danger small mt-1">{{ fieldError('items.0.unit_price') }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="editingId" class="col-12">
                            <hr class="my-2">
                            <h5 class="font-size-14 mb-3">القيود المحاسبية</h5>
                            <div v-if="detailsLoading" class="text-muted small">جاري تحميل القيود...</div>
                            <div v-else-if="!journalEntryRows(journalEntries).length" class="alert alert-warning mb-0 py-2">
                                لا توجد قيود محاسبية. تأكد أن الحالة «معلقة» أو «مدفوعة» وليست «مسودة».
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الوصف</th>
                                            <th>الحساب</th>
                                            <th>مدين</th>
                                            <th>دائن</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(line, index) in journalEntryRows(journalEntries)" :key="`${line.entry_id}-${index}`">
                                            <td>{{ line.entry_date ?? '—' }}</td>
                                            <td>{{ line.description ?? '—' }}</td>
                                            <td>{{ line.account_code }} — {{ line.account_name }}</td>
                                            <td>{{ line.debit > 0 ? formatAmount(line.debit) : '—' }}</td>
                                            <td>{{ line.credit > 0 ? formatAmount(line.credit) : '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
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

    <div class="modal fade" :class="{ show: showView }" :style="{ display: showView ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">القيود المحاسبية — {{ viewInvoice?.invoice_number ?? '' }}</h5>
                    <button type="button" class="btn-close" @click="showView = false"></button>
                </div>
                <div class="modal-body">
                    <div v-if="viewInvoice" class="row g-2 mb-3">
                        <div class="col-md-4"><span class="text-muted">العميل:</span> {{ customerName(viewInvoice) }}</div>
                        <div class="col-md-4"><span class="text-muted">الإجمالي:</span> {{ formatAmount(viewInvoice.total) }}</div>
                        <div class="col-md-4"><span class="text-muted">الحالة:</span> {{ statusLabel(viewInvoice.status) }}</div>
                    </div>
                    <div v-if="detailsLoading" class="text-center py-4 text-muted">جاري تحميل القيود...</div>
                    <div v-else-if="!journalEntryRows(journalEntries).length" class="alert alert-warning mb-0">
                        لا توجد قيود محاسبية لهذه الفاتورة.
                        <span v-if="viewInvoice?.status === 'draft'"> الفاتورة مسودة — غيّر الحالة إلى «معلقة» لإنشاء القيد تلقائياً.</span>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الوصف</th>
                                    <th>الحساب</th>
                                    <th>مدين</th>
                                    <th>دائن</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, index) in journalEntryRows(journalEntries)" :key="`view-${line.entry_id}-${index}`">
                                    <td>{{ line.entry_date ?? '—' }}</td>
                                    <td>{{ line.description ?? '—' }}</td>
                                    <td>{{ line.account_code }} — {{ line.account_name }}</td>
                                    <td>{{ line.debit > 0 ? formatAmount(line.debit) : '—' }}</td>
                                    <td>{{ line.credit > 0 ? formatAmount(line.credit) : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="showView = false">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="showView" class="modal-backdrop fade show"></div>
</template>
