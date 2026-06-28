<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useAdminCrud } from '@/composables/useAdminCrud';
import { useAuthStore } from '@/stores/auth';
import SearchBar from '@/components/admin/SearchBar.vue';
import TableSkeleton from '@/components/admin/TableSkeleton.vue';
import TenantActionModal from '@/components/admin/TenantActionModal.vue';
import RoleSelect from '@/components/admin/RoleSelect.vue';
import PasswordInput from '@/components/admin/PasswordInput.vue';
import { ADMIN_API_ROUTES, PERMISSIONS, ROLES, USER_STATUSES } from '@/utils/constants';
import { unwrapApiData } from '@/utils/apiResponse';
import { notifySuccess } from '@/utils/apiHandler';
import { formatDate } from '@/utils/date.js';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const isSuperAdmin = computed(() => authStore.isSuperAdmin);
const isCompanyAdmin = computed(() => authStore.isCompanyAdmin);
const canCreateUser = computed(() => authStore.hasPermission(PERMISSIONS.CREATE_USER));
const canUpdateUser = computed(() => authStore.hasPermission(PERMISSIONS.UPDATE_USER));
const canDeleteUser = computed(() => authStore.hasPermission(PERMISSIONS.DELETE_USER));
const canManageUsers = computed(() => canUpdateUser.value || canDeleteUser.value);
const tableColspan = computed(() => {
    let cols = 6;
    if (isSuperAdmin.value) {
        cols += 2;
    }
    if (canManageUsers.value) {
        cols += 1;
    }
    return cols;
});

const {
    items: users,
    pagination,
    loading,
    saving,
    errorMessage,
    errors,
    fetchList,
    create,
    update,
    removeConfirmed,
} = useAdminCrud(ADMIN_API_ROUTES.USERS, { resourceLabel: 'المستخدم' });

const search = ref('');
const statusFilter = ref('');
const page = ref(1);

const totalUsers = computed(() => pagination.value?.total ?? 0);
const showForm = ref(false);
const showOwnerAction = ref(false);
const editingId = ref(null);
const deleteTarget = ref(null);
const tenantOptions = ref([]);
const roleOptions = ref([]);

const emptyForm = () => ({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    status: 'active',
    tenant_id: null,
    role: null,
});

const form = reactive(emptyForm());

const modalTitle = computed(() => (editingId.value ? 'تعديل مستخدم' : 'إضافة مستخدم'));

function fieldError(field) {
    const value = errors.value?.[field];
    return Array.isArray(value) ? value[0] : value;
}

async function loadUsers() {
    await fetchList({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        page: page.value,
        paginate: 10,
    });
}

async function loadTenantOptions() {
    if (!isSuperAdmin.value) {
        return;
    }

    const response = await api.get(ADMIN_API_ROUTES.TENANT_OPTIONS);
    tenantOptions.value = unwrapApiData(response) ?? [];
}

async function loadRoleOptions() {
    if (!canCreateUser.value) {
        roleOptions.value = [];
        return;
    }

    if (isSuperAdmin.value) {
        roleOptions.value = [
            { name: ROLES.SUPER_ADMIN, label: 'Super Admin' },
            { name: ROLES.COMPANY_ADMIN, label: 'Company Admin' },
        ];
        return;
    }

    try {
        const response = await api.get(ADMIN_API_ROUTES.ROLE_OPTIONS);
        const data = unwrapApiData(response);
        const list = Array.isArray(data) ? data : [];
        roleOptions.value = list.map((role) => ({ name: role.name, label: role.name }));
    } catch {
        roleOptions.value = [];
    }
}

function assignableRoles(userRoles = []) {
    if (isSuperAdmin.value) {
        return [...userRoles];
    }

    return userRoles.filter((role) => !['Super_Admin', 'Company_Admin'].includes(role));
}

function openCreate() {
    editingId.value = null;
    Object.assign(form, emptyForm());
    loadRoleOptions();
    showForm.value = true;
}

function openEdit(user) {
    editingId.value = user.id;
    Object.assign(form, {
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        status: user.status,
        tenant_id: user.tenant_id,
        role: assignableRoles(user.roles ?? [])[0] ?? null,
    });
    loadRoleOptions();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingId.value = null;
    errors.value = {};
}

async function handleDelete(user) {
    if (isSuperAdmin.value && user.is_owner) {
        deleteTarget.value = user;
        showOwnerAction.value = true;
        return;
    }

    const confirmed = await removeConfirmed(user.id, { label: user.name });

    if (confirmed) {
        await loadUsers();
    }
}

function closeOwnerAction() {
    showOwnerAction.value = false;
    deleteTarget.value = null;
}

async function confirmOwnerAction(action) {
    if (!deleteTarget.value) {
        return;
    }

    saving.value = true;
    errorMessage.value = '';

    try {
        const response = await api.delete(`${ADMIN_API_ROUTES.USERS}/${deleteTarget.value.id}`, {
            data: { action },
        });
        notifySuccess(response);
        closeOwnerAction();
        await loadUsers();
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'تعذر تنفيذ الإجراء.';
    } finally {
        saving.value = false;
    }
}

function goToCreateRole() {
    closeForm();
    router.push({ name: 'admin.roles', query: { action: 'create' } });
}

async function submitForm() {
    const payload = {
        name: form.name,
        email: form.email,
        status: form.status,
        roles: form.role ? [form.role] : [],
    };

    if (isSuperAdmin.value) {
        payload.tenant_id = form.tenant_id;
    }

    if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
    } else if (!editingId.value) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
    }

    try {
        if (editingId.value) {
            await update(editingId.value, payload);
        } else {
            await create(payload);
        }

        closeForm();
        await loadUsers();
    } catch {
        // errors handled in composable
    }
}

function onSearch(value) {
    search.value = value;
    page.value = 1;
    loadUsers();
}

function onStatusChange() {
    page.value = 1;
    loadUsers();
}

function changePage(nextPage) {
    page.value = nextPage;
    loadUsers();
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

onMounted(async () => {
    await loadUsers();

    if (canCreateUser.value) {
        await Promise.all([loadTenantOptions(), loadRoleOptions()]);
    }

    if (route.query.action === 'create' && canCreateUser.value) {
        openCreate();
        router.replace({ name: 'admin.users' });
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
                            <h4 class="card-title mb-1">المستخدمون</h4>
                            <p class="text-muted mb-0 d-flex align-items-center flex-wrap gap-2">
                                <span>{{ isSuperAdmin ? 'مديرو الشركات (Company Admin) فقط' : 'إدارة مستخدمي الشركة' }}</span>
                                <span class="badge bg-primary rounded-pill">{{ totalUsers }} مستخدم</span>
                            </p>
                        </div>
                        <button
                            v-if="canCreateUser"
                            type="button"
                            class="btn btn-primary"
                            @click="openCreate"
                        >
                            <i class="bx bx-plus me-1"></i>
                            إضافة مستخدم
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
                                    <th>الاسم</th>
                                    <th>البريد</th>
                                    <th>الحالة</th>
                                    <th>الأدوار</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th v-if="isSuperAdmin">الشركة</th>
                                    <th v-if="isSuperAdmin">عدد المستخدمين</th>
                                    <th v-if="canManageUsers">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <TableSkeleton v-if="loading" :rows="6" :columns="tableColspan" />
                                <tr v-else-if="!users.length">
                                    <td :colspan="tableColspan" class="text-center py-4 text-muted">
                                        لا يوجد مستخدمون
                                    </td>
                                </tr>
                                <tr v-for="user in users" v-else :key="user.id">
                                    <td>{{ user.id }}</td>
                                    <td>{{ user.name }}</td>
                                    <td>{{ user.email }}</td>
                                    <td>
                                        <span :class="statusClass(user.status)">
                                            {{ statusLabel(user.status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            v-for="role in user.roles"
                                            :key="role"
                                            class="badge bg-primary me-1"
                                        >
                                            {{ role }}
                                        </span>
                                        <span v-if="user.is_owner" class="badge bg-warning text-dark">
                                            صاحب الشركة
                                        </span>
                                    </td>
                                    <td>{{ formatDate(user.created_at) }}</td>
                                    <td v-if="isSuperAdmin">
                                        {{ user.company?.name ?? '—' }}
                                    </td>
                                    <td v-if="isSuperAdmin">
                                        <span class="badge bg-info">{{ user.tenant_users_count ?? 0 }}</span>
                                    </td>
                                    <td v-if="canManageUsers">
                                        <button
                                            v-if="canUpdateUser"
                                            type="button"
                                            class="btn btn-sm btn-soft-primary me-1"
                                            @click="openEdit(user)"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                        <button
                                            v-if="canDeleteUser && user.id !== authStore.user?.id"
                                            type="button"
                                            class="btn btn-sm btn-soft-danger"
                                            @click="handleDelete(user)"
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
                            — {{ totalUsers }} مستخدم
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="form-control"
                                    maxlength="15"
                                    required
                                >
                                <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">
                                    لا يزيد عن 15 حرف
                                </p>
                                <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input v-model="form.email" type="email" class="form-control" required>
                                <p v-if="!fieldError('email')" class="text-muted small mt-1 mb-0">
                                    مطلوب — بريد فريد لتسجيل الدخول
                                </p>
                                <div v-if="fieldError('email')" class="text-danger small mt-1">{{ fieldError('email') }}</div>
                            </div>
                            <div class="col-md-6">
                                <PasswordInput
                                    v-model="form.password"
                                    :label="editingId ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور'"
                                    :required="!editingId"
                                    autocomplete="new-password"
                                    :invalid="Boolean(fieldError('password'))"
                                    :error="fieldError('password')"
                                    hint="لا تقل عن 8 حروف وأرقام"
                                />
                            </div>
                            <div class="col-md-6">
                                <PasswordInput
                                    v-model="form.password_confirmation"
                                    label="تأكيد كلمة المرور"
                                    :required="!editingId && !!form.password"
                                    autocomplete="new-password"
                                    :invalid="Boolean(fieldError('password_confirmation'))"
                                    :error="fieldError('password_confirmation')"
                                    hint="يجب أن يطابق كلمة المرور أعلاه"
                                />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحالة</label>
                                <select v-model="form.status" class="form-select">
                                    <option v-for="status in USER_STATUSES" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <p v-if="!fieldError('status')" class="text-muted small mt-1 mb-0">
                                    «نشط» يسمح بالدخول — «غير نشط» يمنع تسجيل الدخول
                                </p>
                                <div v-if="fieldError('status')" class="text-danger small mt-1">{{ fieldError('status') }}</div>
                            </div>
                            <div v-if="isSuperAdmin" class="col-md-6">
                                <label class="form-label">المستأجر</label>
                                <select v-model="form.tenant_id" class="form-select">
                                    <option :value="null">بدون مستأجر (Super Admin)</option>
                                    <option v-for="tenant in tenantOptions" :key="tenant.id" :value="tenant.id">
                                        {{ tenant.name }}
                                    </option>
                                </select>
                                <p v-if="!fieldError('tenant_id')" class="text-muted small mt-1 mb-0">
                                    اختياري — اربط المستخدم بشركة أو اتركه لمدير المنصة
                                </p>
                                <div v-if="fieldError('tenant_id')" class="text-danger small mt-1">{{ fieldError('tenant_id') }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">الدور</label>
                                <RoleSelect
                                    v-model="form.role"
                                    :options="roleOptions"
                                    :invalid="Boolean(fieldError('roles'))"
                                />
                                <p v-if="!fieldError('roles') && !(isCompanyAdmin && !roleOptions.length)" class="text-muted small mt-1 mb-0">
                                    مطلوب — يحدد صلاحيات المستخدم في النظام
                                </p>
                                <p v-if="isCompanyAdmin && !roleOptions.length" class="text-muted small mt-1 mb-0">
                                    لا توجد أدوار — أنشئ دوراً من صفحة الأدوار أولاً.
                                </p>
                                <div v-if="fieldError('roles')" class="text-danger small mt-1">{{ fieldError('roles') }}</div>
                                <button
                                    v-if="canCreateUser && authStore.hasPermission(PERMISSIONS.CREATE_ROLE)"
                                    type="button"
                                    class="btn btn-link btn-sm text-primary p-0 mt-1"
                                    @click="goToCreateRole"
                                >
                                    <i class="bx bx-plus me-1"></i>
                                    إضافة دور جديد
                                </button>
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

    <TenantActionModal
        :show="showOwnerAction"
        :title="`إدارة شركة: ${deleteTarget?.company?.name ?? ''}`"
        :message="`صاحب الشركة «${deleteTarget?.name ?? ''}» — ${deleteTarget?.tenant_users_count ?? 0} مستخدم. اختر الإجراء:`"
        :loading="saving"
        @close="closeOwnerAction"
        @confirm="confirmOwnerAction"
    />
</template>
