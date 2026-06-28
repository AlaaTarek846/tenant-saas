<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '@/services/api';
import { useAdminCrud } from '@/composables/useAdminCrud';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import TenantActionModal from '@/components/admin/TenantActionModal.vue';
import { ADMIN_API_ROUTES, USER_STATUSES } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const {
    items: tenants,
    pagination,
    loading,
    saving,
    errorMessage,
    fetchList,
} = useAdminCrud(ADMIN_API_ROUTES.TENANTS);

const search = ref('');
const statusFilter = ref('');
const page = ref(1);

const totalTenants = computed(() => pagination.value?.total ?? 0);
const tableColumns = 10;
const showAction = ref(false);
const actionTarget = ref(null);

async function loadTenants() {
    await fetchList({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        page: page.value,
        paginate: 10,
    });
}

function openAction(tenant) {
    actionTarget.value = tenant;
    showAction.value = true;
}

function closeAction() {
    showAction.value = false;
    actionTarget.value = null;
}

async function confirmAction(action) {
    if (!actionTarget.value) {
        return;
    }

    saving.value = true;
    errorMessage.value = '';

    try {
        await api.delete(`${ADMIN_API_ROUTES.TENANTS}/${actionTarget.value.id}`, {
            data: { action },
        });
        closeAction();
        await loadTenants();
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تنفيذ الإجراء.';
    } finally {
        saving.value = false;
    }
}

function onSearch(value) {
    search.value = value;
    page.value = 1;
    loadTenants();
}

function onStatusChange() {
    page.value = 1;
    loadTenants();
}

function changePage(nextPage) {
    page.value = nextPage;
    loadTenants();
}

function statusLabel(status) {
    return USER_STATUSES.find((item) => item.value === status)?.label ?? status;
}

function statusClass(status) {
    if (status === 'active') {
        return 'badge bg-success';
    }

    if (status === 'suspended') {
        return 'badge bg-danger';
    }

    return 'badge bg-secondary';
}

onMounted(loadTenants);
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">المستأجرون</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة شركات المنصة — Super Admin فقط</span>
                                <span class="badge bg-primary rounded-pill">{{ totalTenants }} شركة</span>
                            </p>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث باسم الشركة أو البريد..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">فلتر بالحالة</label>
                            <select v-model="statusFilter" class="form-select" @change="onStatusChange">
                                <option value="">كل الحالات</option>
                                <option v-for="status in USER_STATUSES" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="errorMessage" class="alert alert-danger mt-3">
                        {{ errorMessage }}
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الشركة</th>
                                    <th>البريد</th>
                                    <th>صاحب الشركة</th>
                                    <th>عدد المستخدمين</th>
                                    <th>العملاء</th>
                                    <th>اشتراكات نشطة</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColumns" />
                                <tr v-else-if="!tenants.length">
                                    <td :colspan="tableColumns" class="text-center py-4 text-muted">
                                        لا توجد شركات
                                    </td>
                                </tr>
                                <tr v-for="tenant in tenants" v-else :key="tenant.id">
                                    <td>{{ tenant.id }}</td>
                                    <td>{{ tenant.name }}</td>
                                    <td>{{ tenant.email }}</td>
                                    <td>
                                        <span v-if="tenant.owner">
                                            {{ tenant.owner.name }}
                                            <span class="d-block text-muted small">{{ tenant.owner.email }}</span>
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ tenant.users_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ tenant.customers_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ tenant.active_subscriptions_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span :class="statusClass(tenant.status)">
                                            {{ statusLabel(tenant.status) }}
                                        </span>
                                    </td>
                                    <td>{{ formatDate(tenant.created_at) }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-danger"
                                            title="حذف أو إيقاف"
                                            @click="openAction(tenant)"
                                        >
                                            <i class="bx bx-cog"></i>
                                            إدارة
                                        </button>
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
                            — {{ totalTenants }} شركة
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

    <TenantActionModal
        :show="showAction"
        :title="`إدارة: ${actionTarget?.name ?? ''}`"
        :message="`الشركة «${actionTarget?.name ?? ''}» — ${actionTarget?.users_count ?? 0} مستخدم. اختر الإجراء:`"
        :loading="saving"
        @close="closeAction"
        @confirm="confirmAction"
    />
</template>
