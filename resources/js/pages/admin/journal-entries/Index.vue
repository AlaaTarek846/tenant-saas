<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, JOURNAL_ENTRY_SOURCES, PERMISSIONS } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_ACCOUNT));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_ACCOUNT));

const { items, pagination, loading, saving, errorMessage, errors, fetchList, fetchOne, create, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.JOURNAL_ENTRIES, { resourceLabel: 'القيد' });

const accountOptions = ref([]);
const optionsLoading = ref(false);
const accountsError = ref('');
const search = ref('');
const sourceFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const page = ref(1);

const totalEntries = computed(() => pagination.value?.total ?? 0);
const showForm = ref(false);
const showView = ref(false);
const viewEntry = ref(null);

const emptyLine = () => ({ account_id: '', debit: '', credit: '', description: '' });
const emptyForm = () => ({
    entry_date: new Date().toISOString().slice(0, 10),
    description: '',
    lines: [emptyLine(), emptyLine()],
});
const form = reactive(emptyForm());

const totalDebit = computed(() => form.lines.reduce((sum, line) => sum + (Number(line.debit) || 0), 0));
const totalCredit = computed(() => form.lines.reduce((sum, line) => sum + (Number(line.credit) || 0), 0));
const isBalanced = computed(() => totalDebit.value > 0 && Math.abs(totalDebit.value - totalCredit.value) < 0.01);

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function lineError(index, field) {
    return fieldError(`lines.${index}.${field}`) ?? fieldError(`lines.${index}`);
}

function formatAmount(amount) {
    if (amount === null || amount === undefined || amount === '') return '—';
    return Number(amount).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function sourceLabel(source) {
    return JOURNAL_ENTRY_SOURCES.find((item) => item.value === source)?.label ?? source ?? '—';
}

function accountLabel(accountId) {
    const account = accountOptions.value.find((item) => item.id === accountId);
    return account ? `${account.code} — ${account.name}` : '—';
}

async function loadAccountOptions() {
    optionsLoading.value = true;
    accountsError.value = '';

    try {
        const response = await api.get(ADMIN_API_ROUTES.ACCOUNTS, { params: { paginate: 50 } });
        const data = unwrapApiData(response);
        accountOptions.value = Array.isArray(data) ? data : data?.data ?? [];
    } catch (error) {
        accountsError.value = error.response?.data?.message ?? 'تعذر تحميل الحسابات.';
    } finally {
        optionsLoading.value = false;
    }
}

async function loadItems() {
    await fetchList({
        search: search.value || undefined,
        source: sourceFilter.value || undefined,
        from: fromDate.value || undefined,
        to: toDate.value || undefined,
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
    Object.assign(form, emptyForm());
    showForm.value = true;
}

async function openView(item) {
    showView.value = true;
    viewEntry.value = null;
    try {
        viewEntry.value = await fetchOne(item.id);
    } catch {
        showView.value = false;
    }
}

async function handleDelete(item) {
    const confirmed = await removeConfirmed(item.id, {
        message: 'هل تريد حذف هذا القيد اليدوي؟',
    });

    if (confirmed) {
        await loadItems();
    }
}

function addLine() {
    form.lines.push(emptyLine());
}

function removeLine(index) {
    if (form.lines.length <= 2) return;
    form.lines.splice(index, 1);
}

function onDebitInput(index) {
    if (form.lines[index].debit) form.lines[index].credit = '';
}

function onCreditInput(index) {
    if (form.lines[index].credit) form.lines[index].debit = '';
}

function payload() {
    return {
        entry_date: form.entry_date,
        description: form.description,
        lines: form.lines.map((line) => ({
            account_id: line.account_id,
            debit: line.debit ? Number(line.debit) : 0,
            credit: line.credit ? Number(line.credit) : 0,
            description: line.description || null,
        })),
    };
}

async function submitForm() {
    try {
        await create(payload());
        showForm.value = false;
        await loadItems();
    } catch { /* errors in composable */ }
}

onMounted(async () => {
    await Promise.all([loadAccountOptions(), loadItems()]);
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">القيود المحاسبية</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>تسجيل وعرض القيود اليدوية والآلية</span>
                                <span class="badge bg-primary rounded-pill">{{ totalEntries }} قيد</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> تسجيل قيد
                        </button>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <SearchBar v-model="search" placeholder="بحث بالوصف..." @search="onSearch" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">فلتر بالمصدر</label>
                            <select v-model="sourceFilter" class="form-select" @change="onFilterChange">
                                <option value="">كل المصادر</option>
                                <option v-for="option in JOURNAL_ENTRY_SOURCES" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">من تاريخ</label>
                            <input v-model="fromDate" type="date" class="form-control" @change="onFilterChange">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">إلى تاريخ</label>
                            <input v-model="toDate" type="date" class="form-control" @change="onFilterChange">
                        </div>
                    </div>

                    <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>تاريخ القيد</th>
                                    <th>الوصف</th>
                                    <th>المصدر</th>
                                    <th>المدين</th>
                                    <th>الدائن</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="8" />
                                <tr v-else-if="!items.length"><td colspan="8" class="text-center py-4 text-muted">لا توجد قيود محاسبية</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.entry_date ?? '—' }}</td>
                                    <td>{{ item.description ?? '—' }}</td>
                                    <td>
                                        <span class="badge" :class="item.is_manual ? 'bg-success' : 'bg-secondary'">
                                            {{ item.source_label ?? sourceLabel(item.source) }}
                                        </span>
                                    </td>
                                    <td>{{ formatAmount(item.total_debit) }}</td>
                                    <td>{{ formatAmount(item.total_credit) }}</td>
                                    <td>{{ formatDate(item.created_at) }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-soft-info me-1" title="عرض" @click="openView(item)"><i class="bx bx-show"></i></button>
                                        <button v-if="canDelete && item.is_manual" type="button" class="btn btn-sm btn-soft-danger" title="حذف" @click="handleDelete(item)"><i class="bx bx-trash"></i></button>
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
                            — {{ totalEntries }} قيد
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

    <!-- Create modal -->
    <div class="modal fade" :class="{ show: showForm }" :style="{ display: showForm ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تسجيل قيد محاسبي يدوي</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">تاريخ القيد</label>
                                <input v-model="form.entry_date" type="date" class="form-control" required>
                                <p v-if="!fieldError('entry_date')" class="text-muted small mt-1 mb-0">مطلوب — تاريخ تسجيل القيد في الدفاتر</p>
                                <div v-if="fieldError('entry_date')" class="text-danger small mt-1">{{ fieldError('entry_date') }}</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">الوصف</label>
                                <input v-model="form.description" type="text" class="form-control" maxlength="255" placeholder="مثال: تسوية نهاية الشهر" required>
                                <p v-if="!fieldError('description')" class="text-muted small mt-1 mb-0">مطلوب — وصف مختصر للقيد (حتى 255 حرف)</p>
                                <div v-if="fieldError('description')" class="text-danger small mt-1">{{ fieldError('description') }}</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="font-size-14 mb-0">سطور القيد</h5>
                                <p class="text-muted small mb-0 mt-1">مطلوب — سطران على الأقل؛ مجموع المدين = مجموع الدائن</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-soft-primary" @click="addLine"><i class="bx bx-plus"></i> إضافة سطر</button>
                        </div>

                        <div v-if="fieldError('lines')" class="alert alert-danger py-2">{{ fieldError('lines') }}</div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحساب</th>
                                        <th style="width: 130px">مدين</th>
                                        <th style="width: 130px">دائن</th>
                                        <th>ملاحظة</th>
                                        <th style="width: 50px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(line, index) in form.lines" :key="index">
                                        <td>
                                            <select v-model="line.account_id" class="form-select form-select-sm" :disabled="optionsLoading" required>
                                                <option value="">اختر الحساب</option>
                                                <option v-for="account in accountOptions" :key="account.id" :value="account.id">
                                                    {{ account.code }} — {{ account.name }}
                                                </option>
                                            </select>
                                            <div v-if="lineError(index, 'account_id')" class="text-danger small">{{ lineError(index, 'account_id') }}</div>
                                        </td>
                                        <td>
                                            <input v-model="line.debit" type="number" min="0" step="0.01" class="form-control form-control-sm" @input="onDebitInput(index)">
                                        </td>
                                        <td>
                                            <input v-model="line.credit" type="number" min="0" step="0.01" class="form-control form-control-sm" @input="onCreditInput(index)">
                                        </td>
                                        <td>
                                            <input v-model="line.description" type="text" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-soft-danger" :disabled="form.lines.length <= 2" @click="removeLine(index)"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>الإجمالي</th>
                                        <th :class="{ 'text-danger': !isBalanced && totalDebit > 0 }">{{ formatAmount(totalDebit) }}</th>
                                        <th :class="{ 'text-danger': !isBalanced && totalCredit > 0 }">{{ formatAmount(totalCredit) }}</th>
                                        <th colspan="2">
                                            <span v-if="isBalanced" class="text-success small">القيد متوازن ✓</span>
                                            <span v-else class="text-muted small">مجموع المدين يجب أن يساوي مجموع الدائن</span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="showForm = false">إلغاء</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving || !isBalanced">{{ saving ? 'جاري الحفظ...' : 'حفظ القيد' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="showForm" class="modal-backdrop fade show"></div>

    <!-- View modal -->
    <div class="modal fade" :class="{ show: showView }" :style="{ display: showView ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل القيد #{{ viewEntry?.id ?? '' }}</h5>
                    <button type="button" class="btn-close" @click="showView = false"></button>
                </div>
                <div class="modal-body">
                    <div v-if="!viewEntry" class="text-center py-4 text-muted">جاري التحميل...</div>
                    <template v-else>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4"><span class="text-muted">التاريخ:</span> {{ viewEntry.entry_date }}</div>
                            <div class="col-md-4"><span class="text-muted">المصدر:</span> {{ viewEntry.source_label }}</div>
                            <div class="col-md-4"><span class="text-muted">الإجمالي:</span> {{ formatAmount(viewEntry.total_debit) }}</div>
                            <div class="col-12"><span class="text-muted">الوصف:</span> {{ viewEntry.description }}</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحساب</th>
                                        <th>مدين</th>
                                        <th>دائن</th>
                                        <th>ملاحظة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="line in viewEntry.details ?? []" :key="line.id">
                                        <td>{{ line.account_code }} — {{ line.account_name }}</td>
                                        <td>{{ line.debit > 0 ? formatAmount(line.debit) : '—' }}</td>
                                        <td>{{ line.credit > 0 ? formatAmount(line.credit) : '—' }}</td>
                                        <td>{{ line.description ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" @click="showView = false">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="showView" class="modal-backdrop fade show"></div>
</template>
