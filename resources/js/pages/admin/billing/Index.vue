<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, PERMISSIONS, ROLES } from '@/utils/constants';

const authStore = useAuthStore();

const canGenerateInvoices = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_INVOICE));
const canRecognizeRevenue = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_INVOICE));
const isSuperAdmin = computed(() => authStore.hasRole(ROLES.SUPER_ADMIN));

const tenantOptions = ref([]);
const loadingTenants = ref(false);
const runningInvoices = ref(false);
const runningRevenue = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const asOfInvoices = ref(new Date().toISOString().slice(0, 10));
const asOfRevenue = ref(endOfMonthIso());
const tenantId = ref('');

const invoiceResult = ref(null);
const revenueResult = ref(null);

function endOfMonthIso(date = new Date()) {
    const end = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    return end.toISOString().slice(0, 10);
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

function billingPayload() {
    const payload = {};
    if (isSuperAdmin.value && tenantId.value) {
        payload.tenant_id = Number(tenantId.value);
    }
    return payload;
}

async function loadTenantOptions() {
    if (!isSuperAdmin.value) return;

    loadingTenants.value = true;
    try {
        const response = await api.get(ADMIN_API_ROUTES.TENANT_OPTIONS);
        tenantOptions.value = unwrapApiData(response) ?? [];
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تحميل الشركات.';
    } finally {
        loadingTenants.value = false;
    }
}

async function generateInvoices() {
    runningInvoices.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    invoiceResult.value = null;

    try {
        const response = await api.post(ADMIN_API_ROUTES.BILLING_GENERATE_INVOICES, {
            ...billingPayload(),
            as_of: asOfInvoices.value,
        });
        invoiceResult.value = unwrapApiData(response);
        successMessage.value = response.data?.message ?? 'تم إنشاء الفواتير الدورية.';
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر إنشاء الفواتير الدورية.';
    } finally {
        runningInvoices.value = false;
    }
}

async function recognizeRevenue() {
    runningRevenue.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    revenueResult.value = null;

    try {
        const response = await api.post(ADMIN_API_ROUTES.BILLING_RECOGNIZE_REVENUE, {
            ...billingPayload(),
            as_of: asOfRevenue.value,
        });
        revenueResult.value = unwrapApiData(response);
        successMessage.value = response.data?.message ?? 'تم اعتراف الإيراد.';
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر اعتراف الإيراد.';
    } finally {
        runningRevenue.value = false;
    }
}

onMounted(loadTenantOptions);
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <h4 class="card-title mb-1">عمليات نهاية الشهر</h4>
                        <p class="text-muted mb-0">محاكاة الفوترة الدورية واعتراف الإيراد المؤجل</p>
                    </div>

                    <div v-if="isSuperAdmin" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">الشركة (Super Admin)</label>
                            <select v-model="tenantId" class="form-select" :disabled="loadingTenants">
                                <option value="">شركتي / الكل حسب السياق</option>
                                <option v-for="tenant in tenantOptions" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                            </select>
                            <p class="text-muted small mt-1 mb-0">مطلوب لاعتراف الإيراد — اختر الشركة المستهدفة</p>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
                    <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h5 class="font-size-15 mb-2">1. الفوترة الدورية</h5>
                                <p class="text-muted small">إنشاء فواتير للاشتراكات النشطة التي حان موعد فوترتها.</p>
                                <div class="mb-3">
                                    <label class="form-label">تاريخ التشغيل (as_of)</label>
                                    <input v-model="asOfInvoices" type="date" class="form-control">
                                    <p class="text-muted small mt-1 mb-0">يُنشئ فواتير للاشتراكات التي حان موعد فوترتها حتى هذا التاريخ</p>
                                </div>
                                <button
                                    v-if="canGenerateInvoices"
                                    type="button"
                                    class="btn btn-primary"
                                    :disabled="runningInvoices"
                                    @click="generateInvoices"
                                >
                                    {{ runningInvoices ? 'جاري التنفيذ...' : 'إنشاء الفواتير الدورية' }}
                                </button>
                                <div v-if="invoiceResult" class="mt-3">
                                    <p class="mb-2 small text-success">تم إنشاء {{ invoiceResult.count ?? 0 }} فاتورة.</p>
                                    <ul v-if="invoiceResult.invoices?.length" class="small mb-0 ps-3">
                                        <li v-for="invoice in invoiceResult.invoices" :key="invoice.invoice_id">
                                            {{ invoice.invoice_number }} — {{ formatAmount(invoice.total) }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <h5 class="font-size-15 mb-2">2. اعتراف بالإيراد (Revenue Recognition)</h5>
                                <p class="text-muted small mb-2">
                                    في نهاية الشهر: تحويل الإيراد المؤجل إلى إيراد فعلي لكل فاتورة لم يُعتَرَف بإيرادها بعد.
                                </p>
                                <div class="alert alert-light py-2 small mb-3">
                                    <strong>القيد المحاسبي لكل فاتورة 100$:</strong><br>
                                    مدين: Deferred Revenue (2100) — 100<br>
                                    دائن: Subscription Revenue (4000) — 100
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">نهاية الفترة (as_of)</label>
                                    <input v-model="asOfRevenue" type="date" class="form-control">
                                    <p class="text-muted small mt-1 mb-0">تاريخ اعتراف الإيراد — يُحوّل المؤجل إلى إيراد فعلي</p>
                                </div>
                                <button
                                    v-if="canRecognizeRevenue"
                                    type="button"
                                    class="btn btn-success"
                                    :disabled="runningRevenue"
                                    @click="recognizeRevenue"
                                >
                                    {{ runningRevenue ? 'جاري الاعتراف...' : 'اعتراف بالإيراد' }}
                                </button>
                                <div v-if="revenueResult" class="mt-3">
                                    <p class="mb-2 small text-success">
                                        تم اعتراف إيراد {{ revenueResult.count ?? 0 }} فاتورة حتى {{ revenueResult.as_of }}.
                                    </p>
                                    <div v-if="journalEntryRows(revenueResult.entries ?? []).length" class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>الوصف</th>
                                                    <th>الحساب</th>
                                                    <th>مدين</th>
                                                    <th>دائن</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(line, index) in journalEntryRows(revenueResult.entries ?? [])" :key="`${line.entry_id}-${index}`">
                                                    <td>{{ line.description }}</td>
                                                    <td>{{ line.account_code }} — {{ line.account_name }}</td>
                                                    <td>{{ line.debit > 0 ? formatAmount(line.debit) : '—' }}</td>
                                                    <td>{{ line.credit > 0 ? formatAmount(line.credit) : '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
