<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, PAYMENT_STATUSES, PERMISSIONS } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_PAYMENT));
const canRead = computed(() => authStore.hasPermission(PERMISSIONS.READ_PAYMENT));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_PAYMENT));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_PAYMENT));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, fetchOne, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.PAYMENTS, { resourceLabel: 'الدفعة' });

const invoiceOptions = ref([]);
const optionsLoading = ref(false);
const search = ref('');
const statusFilter = ref('');
const page = ref(1);

const totalPayments = computed(() => pagination.value?.total ?? 0);
const showForm = ref(false);
const showView = ref(false);
const editingId = ref(null);
const viewPayment = ref(null);
const journalEntries = ref([]);
const detailsLoading = ref(false);

const emptyForm = () => ({
    invoice_id: '',
    payment_method: '',
    reference: '',
    amount: '',
    paid_at: '',
    status: 'paid',
});
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function statusLabel(status) {
    return PAYMENT_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function formatAmount(amount) {
    if (amount === null || amount === undefined || amount === '') return '—';
    return Number(amount).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
    })));
}

async function loadPaymentDetails(id) {
    detailsLoading.value = true;
    try {
        const payment = await fetchOne(id);
        journalEntries.value = payment?.journal_entries ?? [];
        return payment;
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل تفاصيل الدفعة.';
        journalEntries.value = [];
        return null;
    } finally {
        detailsLoading.value = false;
    }
}

function invoiceLabel(invoice) {
    if (!invoice) return '—';
    const number = invoice.invoice_number ?? `#${invoice.id}`;
    const customer = invoice.customer?.name ? ` - ${invoice.customer.name}` : '';
    const total = invoice.total !== undefined ? ` - ${formatAmount(invoice.total)}` : '';
    return `${number}${customer}${total}`;
}

function invoiceFromPayment(item) {
    return item.invoice ?? invoiceOptions.value.find((option) => option.id === item.invoice_id);
}

function toDateTimeLocal(value) {
    if (!value) return '';
    return String(value).replace(' ', 'T').slice(0, 16);
}

async function loadInvoiceOptions() {
    optionsLoading.value = true;
    try {
        const response = await api.get(ADMIN_API_ROUTES.INVOICES, { params: { paginate: 50 } });
        const data = unwrapApiData(response);
        invoiceOptions.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل الفواتير.';
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
    viewPayment.value = item;
    showView.value = true;
    await loadPaymentDetails(item.id);
}

async function openEdit(item) {
    editingId.value = item.id;
    Object.assign(form, {
        invoice_id: item.invoice_id ?? '',
        payment_method: item.payment_method ?? '',
        reference: item.reference ?? '',
        amount: item.amount ?? '',
        paid_at: toDateTimeLocal(item.paid_at),
        status: item.status ?? 'paid',
    });
    showForm.value = true;
    await loadPaymentDetails(item.id);
}

async function handleDelete(item) {
    const confirmed = await removeConfirmed(item.id, {
        message: `هل تريد حذف الدفعة رقم ${item.id}؟`,
    });

    if (confirmed) {
        await loadItems();
    }
}

async function submitForm() {
    try {
        const payload = { ...form, reference: form.reference || null };
        let saved;
        if (editingId.value) saved = await update(editingId.value, payload);
        else saved = await create(payload);

        if (saved?.journal_entries) {
            journalEntries.value = saved.journal_entries;
        }

        showForm.value = false;
        await Promise.all([loadItems(), loadInvoiceOptions()]);
    } catch { /* errors in composable */ }
}

onMounted(async () => {
    await Promise.all([loadInvoiceOptions(), loadItems()]);
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">المدفوعات</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة مدفوعات الفواتير</span>
                                <span class="badge bg-primary rounded-pill">{{ totalPayments }} دفعة</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة دفعة
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث بالمرجع أو رقم الفاتورة..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onStatusChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in PAYMENT_STATUSES" :key="status.value" :value="status.value">
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
                                    <th>#</th><th>الفاتورة</th><th>طريقة الدفع</th><th>المرجع</th><th>المبلغ</th><th>تاريخ الإنشاء</th><th>الحالة</th>
                                    <th v-if="canRead || canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="canRead || canManage ? 8 : 7" />
                                <tr v-else-if="!items.length"><td :colspan="canRead || canManage ? 8 : 7" class="text-center py-4 text-muted">لا توجد مدفوعات</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ invoiceLabel(invoiceFromPayment(item)) }}</td>
                                    <td>{{ item.payment_method }}</td>
                                    <td>{{ item.reference ?? '—' }}</td>
                                    <td>{{ formatAmount(item.amount) }}</td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td><span class="badge bg-primary">{{ statusLabel(item.status) }}</span></td>
                                    <td v-if="canRead || canManage">
                                        <button v-if="canRead" type="button" class="btn btn-sm btn-soft-info me-1" title="عرض القيد المحاسبي" @click="openView(item)"><i class="bx bx-show"></i></button>
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
                            — {{ totalPayments }} دفعة
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
                    <h5 class="modal-title">{{ editingId ? 'تعديل دفعة' : 'إضافة دفعة' }}</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الفاتورة</label>
                            <select v-model="form.invoice_id" class="form-select" :disabled="optionsLoading" required>
                                <option value="">اختر الفاتورة</option>
                                <option v-for="invoice in invoiceOptions" :key="invoice.id" :value="invoice.id">{{ invoiceLabel(invoice) }}</option>
                            </select>
                            <p v-if="!fieldError('invoice_id')" class="text-muted small mt-1 mb-0">مطلوب — الفاتورة المرتبطة بالدفعة</p>
                            <div v-if="fieldError('invoice_id')" class="text-danger small mt-1">{{ fieldError('invoice_id') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">طريقة الدفع</label>
                            <input v-model="form.payment_method" type="text" class="form-control" maxlength="50" required>
                            <p v-if="!fieldError('payment_method')" class="text-muted small mt-1 mb-0">مطلوب — مثل: نقدي، تحويل بنكي (حتى 50 حرف)</p>
                            <div v-if="fieldError('payment_method')" class="text-danger small mt-1">{{ fieldError('payment_method') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المرجع</label>
                            <input v-model="form.reference" type="text" class="form-control" maxlength="255">
                            <p v-if="!fieldError('reference')" class="text-muted small mt-1 mb-0">اختياري — رقم إيصال أو مرجع التحويل</p>
                            <div v-if="fieldError('reference')" class="text-danger small mt-1">{{ fieldError('reference') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المبلغ</label>
                            <input v-model="form.amount" type="number" min="0.01" step="0.01" class="form-control" required>
                            <p v-if="!fieldError('amount')" class="text-muted small mt-1 mb-0">مطلوب — مبلغ التحصيل (0.01 أو أكثر)</p>
                            <div v-if="fieldError('amount')" class="text-danger small mt-1">{{ fieldError('amount') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الدفع</label>
                            <input v-model="form.paid_at" type="datetime-local" class="form-control" required>
                            <p v-if="!fieldError('paid_at')" class="text-muted small mt-1 mb-0">مطلوب — تاريخ ووقت استلام الدفعة</p>
                            <div v-if="fieldError('paid_at')" class="text-danger small mt-1">{{ fieldError('paid_at') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الحالة</label>
                            <select v-model="form.status" class="form-select">
                                <option v-for="s in PAYMENT_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                <template v-if="form.status === 'paid'">«مدفوع» يُسجّل: مدين نقدية / دائن ذمم مدينة</template>
                                <template v-else>القيد المحاسبي يُسجَّل فقط للدفعات بحالة «مدفوع»</template>
                            </p>
                            <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                        </div>
                        <div v-if="editingId" class="col-12">
                            <hr class="my-2">
                            <h5 class="font-size-14 mb-3">القيد المحاسبي</h5>
                            <div v-if="detailsLoading" class="text-muted small">جاري تحميل القيد...</div>
                            <div v-else-if="!journalEntryRows(journalEntries).length" class="alert alert-warning mb-0 py-2">
                                لا يوجد قيد محاسبي. تأكد أن الحالة «مدفوع».
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
                    <h5 class="modal-title">القيد المحاسبي — دفعة #{{ viewPayment?.id ?? '' }}</h5>
                    <button type="button" class="btn-close" @click="showView = false"></button>
                </div>
                <div class="modal-body">
                    <div v-if="viewPayment" class="row g-2 mb-3">
                        <div class="col-md-4"><span class="text-muted">الفاتورة:</span> {{ invoiceLabel(invoiceFromPayment(viewPayment)) }}</div>
                        <div class="col-md-4"><span class="text-muted">المبلغ:</span> {{ formatAmount(viewPayment.amount) }}</div>
                        <div class="col-md-4"><span class="text-muted">الحالة:</span> {{ statusLabel(viewPayment.status) }}</div>
                    </div>
                    <div v-if="detailsLoading" class="text-center py-4 text-muted">جاري التحميل...</div>
                    <div v-else-if="!journalEntryRows(journalEntries).length" class="alert alert-warning mb-0">
                        لا يوجد قيد محاسبي. الدفعة يجب أن تكون بحالة «مدفوع».
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
