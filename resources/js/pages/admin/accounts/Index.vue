<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import { ACCOUNT_TYPES, ADMIN_API_ROUTES, PERMISSIONS } from '@/utils/constants';
import { formatDate } from '@/utils/date.js';

const authStore = useAuthStore();
const canCreate = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_ACCOUNT));
const canUpdate = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_ACCOUNT));
const canDelete = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_ACCOUNT));
const canManage = computed(() => canUpdate.value || canDelete.value);

const { items, pagination, loading, saving, errorMessage, errors, fetchList, create, update, removeConfirmed } =
    useAdminCrud(ADMIN_API_ROUTES.ACCOUNTS, { resourceLabel: 'الحساب' });

const search = ref('');
const typeFilter = ref('');
const page = ref(1);

const totalAccounts = computed(() => pagination.value?.total ?? 0);
const showForm = ref(false);
const editingId = ref(null);

const emptyForm = () => ({ code: '', name: '', type: 'Asset' });
const form = reactive(emptyForm());

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function typeLabel(type) {
    return ACCOUNT_TYPES.find((item) => item.value === type)?.label ?? type;
}

async function loadItems() {
    await fetchList({
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        page: page.value,
        paginate: 10,
    });
}

function onSearch(value) {
    search.value = value;
    page.value = 1;
    loadItems();
}

function onTypeChange() {
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
    Object.assign(form, { code: item.code ?? '', name: item.name ?? '', type: item.type ?? 'Asset' });
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
                            <h4 class="card-title mb-1">الحسابات</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إدارة دليل الحسابات</span>
                                <span class="badge bg-primary rounded-pill">{{ totalAccounts }} حساب</span>
                            </p>
                        </div>
                        <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                            <i class="bx bx-plus me-1"></i> إضافة حساب
                        </button>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <SearchBar
                                v-model="search"
                                placeholder="بحث بالكود أو الاسم..."
                                @search="onSearch"
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">فلتر بالنوع</label>
                            <select v-model="typeFilter" class="form-select" @change="onTypeChange">
                                <option value="">كل الأنواع</option>
                                <option v-for="type in ACCOUNT_TYPES" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div v-if="errorMessage" class="alert alert-danger mt-3">{{ errorMessage }}</div>
                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>الكود</th><th>الاسم</th><th>النوع</th><th>تاريخ الإنشاء</th>
                                    <th v-if="canManage">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="canManage ? 6 : 5" />
                                <tr v-else-if="!items.length"><td :colspan="canManage ? 6 : 5" class="text-center py-4 text-muted">لا توجد حسابات</td></tr>
                                <tr v-for="item in items" v-else :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.code }}</td>
                                    <td>{{ item.name }}</td>
                                    <td><span class="badge bg-primary">{{ typeLabel(item.type) }}</span></td>
                                    <td>{{ formatDate(item.created_at) }}</td>
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
                            — {{ totalAccounts }} حساب
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
                    <h5 class="modal-title">{{ editingId ? 'تعديل حساب' : 'إضافة حساب' }}</h5>
                    <button type="button" class="btn-close" @click="showForm = false"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الكود</label>
                            <input v-model="form.code" type="text" class="form-control" maxlength="20" required>
                            <p v-if="!fieldError('code')" class="text-muted small mt-1 mb-0">مطلوب — رقم الحساب في دليل الحسابات (حتى 20 حرف)</p>
                            <div v-if="fieldError('code')" class="text-danger small mt-1">{{ fieldError('code') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم</label>
                            <input v-model="form.name" type="text" class="form-control" maxlength="255" required>
                            <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">مطلوب — اسم الحساب بالإنجليزية أو العربية</p>
                            <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">النوع</label>
                            <select v-model="form.type" class="form-select" required>
                                <option v-for="type in ACCOUNT_TYPES" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </select>
                            <p v-if="!fieldError('type')" class="text-muted small mt-1 mb-0">مطلوب — أصول، خصوم، إيرادات، أو مصروفات</p>
                            <div v-if="fieldError('type')" class="text-danger small mt-1">{{ fieldError('type') }}</div>
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
