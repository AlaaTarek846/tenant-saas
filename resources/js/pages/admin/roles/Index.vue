<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import PermissionGroupSelect from '@/components/admin/PermissionGroupSelect.vue';
import { ADMIN_API_ROUTES, PERMISSIONS } from '@/utils/constants';
import { unwrapApiData } from '@/utils/apiResponse';
import { formatDate } from '@/utils/date.js';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const canCreateRole = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_ROLE));
const canUpdateRole = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_ROLE));
const canDeleteRole = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_ROLE));
const canManageRoles = computed(() => canUpdateRole.value || canDeleteRole.value);

const {
    items: roles,
    pagination,
    loading,
    saving,
    errorMessage,
    errors,
    fetchList,
    fetchOne,
    create,
    update,
    removeConfirmed,
} = useAdminCrud(ADMIN_API_ROUTES.ROLES, { resourceLabel: 'الدور' });

const permissions = ref([]);
const search = ref('');
const page = ref(1);

const totalRoles = computed(() => pagination.value?.total ?? 0);
const showForm = ref(false);
const editingId = ref(null);

const emptyForm = () => ({
    name: '',
    permissions: [],
});

const form = reactive(emptyForm());

const modalTitle = computed(() => (editingId.value ? 'تعديل دور' : 'إضافة دور'));
const tableColumns = computed(() => (canManageRoles.value ? 5 : 4));

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

function permissionsCount(role) {
    return role.permissions_count ?? role.permissions?.length ?? 0;
}

async function loadRoles() {
    await fetchList({
        search: search.value || undefined,
        page: page.value,
        paginate: 10,
    });
}

async function loadPermissions() {
    const response = await api.get(ADMIN_API_ROUTES.PERMISSIONS);
    permissions.value = unwrapApiData(response) ?? [];
}

function openCreate() {
    editingId.value = null;
    Object.assign(form, emptyForm());
    loadPermissions();
    showForm.value = true;
}

async function openEdit(role) {
    editingId.value = role.id;
    loadPermissions();
    showForm.value = true;

    try {
        const fullRole = await fetchOne(role.id);
        Object.assign(form, {
            name: fullRole.name,
            permissions: [...(fullRole.permissions ?? [])],
        });
    } catch {
        closeForm();
    }
}

function closeForm() {
    showForm.value = false;
    editingId.value = null;
    errors.value = {};
}

async function handleDelete(role) {
    const confirmed = await removeConfirmed(role.id, { label: role.name });

    if (confirmed) {
        await loadRoles();
    }
}

async function submitForm() {
    const payload = {
        name: form.name,
        permissions: form.permissions,
    };

    try {
        if (editingId.value) {
            await update(editingId.value, payload);
        } else {
            await create(payload);
        }

        closeForm();
        await loadRoles();
    } catch {
        // handled in composable
    }
}

function onSearch(value) {
    search.value = value;
    page.value = 1;
    loadRoles();
}

function changePage(nextPage) {
    page.value = nextPage;
    loadRoles();
}

onMounted(async () => {
    await Promise.all([loadRoles(), loadPermissions()]);

    if (route.query.action === 'create') {
        openCreate();
        router.replace({ name: 'admin.roles' });
    }
});
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h4 class="card-title mb-1">الأدوار والصلاحيات</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>إنشاء أدوار مخصصة وربطها بالصلاحيات</span>
                                <span class="badge bg-primary rounded-pill">{{ totalRoles }} دور</span>
                            </p>
                        </div>
                        <button
                            v-if="canCreateRole"
                            type="button"
                            class="btn btn-primary"
                            @click="openCreate"
                        >
                            <i class="bx bx-plus me-1"></i>
                            إضافة دور
                        </button>
                    </div>

                    <SearchBar
                        v-model="search"
                        placeholder="بحث باسم الدور..."
                        @search="onSearch"
                    />

                    <div v-if="errorMessage" class="alert alert-danger mt-3">
                        {{ errorMessage }}
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الدور</th>
                                    <th>عدد الصلاحيات</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th v-if="canManageRoles">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColumns" />
                                <tr v-else-if="!roles.length">
                                    <td :colspan="tableColumns" class="text-center py-4 text-muted">لا توجد أدوار</td>
                                </tr>
                                <tr v-for="role in roles" v-else :key="role.id">
                                    <td>{{ role.id }}</td>
                                    <td>{{ role.name }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ permissionsCount(role) }} صلاحية</span>
                                    </td>
                                    <td>{{ formatDate(role.created_at) }}</td>
                                    <td v-if="canManageRoles">
                                        <button
                                            v-if="canUpdateRole"
                                            type="button"
                                            class="btn btn-sm btn-soft-primary me-1"
                                            @click="openEdit(role)"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                        <button
                                            v-if="canDeleteRole"
                                            type="button"
                                            class="btn btn-sm btn-soft-danger"
                                            @click="handleDelete(role)"
                                        >
                                            <i class="bx bx-trash"></i>
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
                            — {{ totalRoles }} دور
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

    <div
        class="modal fade"
        :class="{ show: showForm }"
        :style="{ display: showForm ? 'block' : 'none' }"
        tabindex="-1"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ modalTitle }}</h5>
                    <button type="button" class="btn-close" @click="closeForm"></button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم الدور</label>
                            <input v-model="form.name" type="text" class="form-control" maxlength="255" required>
                            <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">
                                مطلوب — اسم فريد للدور (لا يمكن تعديل أدوار النظام)
                            </p>
                            <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                        </div>

                        <div>
                            <label class="form-label d-block mb-2">الصلاحيات</label>
                            <p v-if="!fieldError('permissions')" class="text-muted small mb-2">
                                اختياري — حدد ما يمكن للمستخدم بهذا الدور فعله
                            </p>
                            <PermissionGroupSelect
                                v-model="form.permissions"
                                :permissions="permissions"
                            />
                            <div v-if="fieldError('permissions')" class="text-danger small mt-1">
                                {{ fieldError('permissions') }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" @click="closeForm">إلغاء</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            {{ saving ? 'جاري الحفظ...' : 'حفظ' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="showForm" class="modal-backdrop fade show"></div>
</template>
