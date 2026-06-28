<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import { unwrapApiData } from '@/utils/apiResponse';
import { ADMIN_API_ROUTES, ROLES } from '@/utils/constants';

const authStore = useAuthStore();
const isSuperAdmin = computed(() => authStore.hasRole(ROLES.SUPER_ADMIN));

const tenantOptions = ref([]);
const tenantId = ref('');
const loadingTenants = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const asOf = ref(new Date().toISOString().slice(0, 10));
const report = ref(null);

function formatAmount(amount) {
    if (amount === null || amount === undefined || amount === '') return '—';
    return Number(amount).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function reportParams() {
    const params = {};
    if (isSuperAdmin.value && tenantId.value) {
        params.tenant_id = tenantId.value;
    }
    return params;
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

async function loadReport() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get(ADMIN_API_ROUTES.REPORTS_BALANCE_SHEET, {
            params: {
                ...reportParams(),
                as_of: asOf.value,
            },
        });
        report.value = unwrapApiData(response);
    } catch (error) {
        report.value = null;
        errorMessage.value = error.response?.data?.message ?? 'تعذر إنشاء الميزانية العمومية.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await loadTenantOptions();
    await loadReport();
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">الميزانية العمومية</h4>
                            <p class="text-muted mb-0">Balance Sheet — أرصدة Cash و Accounts Receivable و Deferred Revenue</p>
                        </div>
                        <button type="button" class="btn btn-primary" :disabled="loading" @click="loadReport">
                            {{ loading ? 'جاري التحديث...' : 'تحديث التقرير' }}
                        </button>
                    </div>

                    <div v-if="isSuperAdmin" class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">الشركة (Super Admin)</label>
                            <select v-model="tenantId" class="form-select" :disabled="loadingTenants" @change="loadReport">
                                <option value="">— اختر الشركة —</option>
                                <option v-for="tenant in tenantOptions" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                            </select>
                            <p class="text-muted small mt-1 mb-0">مطلوب لمدير المنصة — اختر الشركة لعرض تقريرها</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">حتى تاريخ</label>
                            <input v-model="asOf" type="date" class="form-control" @change="loadReport">
                            <p class="text-muted small mt-1 mb-0">تاريخ قفل الأرصدة — Cash و AR و Deferred Revenue</p>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

                    <div v-if="loading" class="text-center py-4 text-muted">جاري تحميل الميزانية العمومية...</div>
                    <template v-else-if="report">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحساب</th>
                                        <th>النوع</th>
                                        <th>الرصيد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="account in report.key_accounts ?? []" :key="account.code">
                                        <td>{{ account.code }} — {{ account.name }}</td>
                                        <td>{{ account.type === 'Asset' ? 'أصول' : 'خصوم' }}</td>
                                        <td>{{ formatAmount(account.balance) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light fw-semibold">
                                    <tr>
                                        <td colspan="2">إجمالي الأصول</td>
                                        <td>{{ formatAmount(report.totals?.assets) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">إجمالي الخصوم</td>
                                        <td>{{ formatAmount(report.totals?.liabilities) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="text-muted small mb-0">حتى: {{ report.as_of }}</p>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
