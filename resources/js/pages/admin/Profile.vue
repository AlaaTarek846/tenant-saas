<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import PasswordInput from '@/components/admin/PasswordInput.vue';
import AvatarUploader from '@/components/admin/AvatarUploader.vue';
import { ADMIN_API_ROUTES } from '@/utils/constants';
import { unwrapApiData } from '@/utils/apiResponse';

const authStore = useAuthStore();

const loading = ref(true);
const savingUser = ref(false);
const savingCompany = ref(false);
const errors = ref({});
const companyErrors = ref({});
const canManageCompany = ref(false);

const userForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    avatar_url: null,
});

const companyForm = reactive({
    name: '',
    email: '',
    country: '',
    city: '',
    phone: '',
    logo_url: null,
});

const userAvatarPreview = ref(null);
const companyLogoPreview = ref(null);
const userAvatarFile = ref(null);
const companyLogoFile = ref(null);

const isCompanyAdmin = computed(() => authStore.isCompanyAdmin);

function fieldError(field, source = 'user') {
    const bag = source === 'company' ? companyErrors.value : errors.value;
    const value = bag?.[field];
    return Array.isArray(value) ? value[0] : value;
}

async function loadProfile() {
    loading.value = true;

    try {
        const response = await api.get(ADMIN_API_ROUTES.PROFILE);
        const data = unwrapApiData(response);

        Object.assign(userForm, {
            name: data.user?.name ?? '',
            email: data.user?.email ?? '',
            password: '',
            password_confirmation: '',
            avatar_url: data.user?.avatar_url ?? null,
        });

        canManageCompany.value = Boolean(data.can_manage_company);
        userAvatarPreview.value = data.user?.avatar_url ?? null;

        if (data.company) {
            Object.assign(companyForm, {
                name: data.company.name ?? '',
                email: data.company.email ?? '',
                country: data.company.country ?? '',
                city: data.company.city ?? '',
                phone: data.company.phone ?? '',
                logo_url: data.company.logo_url ?? null,
            });
            companyLogoPreview.value = data.company.logo_url ?? null;
        }
    } finally {
        loading.value = false;
    }
}

function onUserAvatarChange({ file, previewUrl }) {
    userAvatarFile.value = file;
    userAvatarPreview.value = previewUrl;
}

function onCompanyLogoChange({ file, previewUrl }) {
    companyLogoFile.value = file;
    companyLogoPreview.value = previewUrl;
}

async function saveUserProfile() {
    savingUser.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('name', userForm.name);
    formData.append('email', userForm.email);

    if (userForm.password) {
        formData.append('password', userForm.password);
        formData.append('password_confirmation', userForm.password_confirmation);
    }

    if (userAvatarFile.value) {
        formData.append('avatar', userAvatarFile.value);
    }

    try {
        await api.post(ADMIN_API_ROUTES.PROFILE, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        userForm.password = '';
        userForm.password_confirmation = '';
        userAvatarFile.value = null;
        await authStore.fetchUser();
        await loadProfile();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        }
    } finally {
        savingUser.value = false;
    }
}

async function saveCompanyProfile() {
    savingCompany.value = true;
    companyErrors.value = {};

    const formData = new FormData();
    formData.append('name', companyForm.name);
    formData.append('email', companyForm.email);
    formData.append('country', companyForm.country ?? '');
    formData.append('city', companyForm.city ?? '');
    formData.append('phone', companyForm.phone ?? '');

    if (companyLogoFile.value) {
        formData.append('logo', companyLogoFile.value);
    }

    try {
        await api.post(ADMIN_API_ROUTES.PROFILE_COMPANY, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        companyLogoFile.value = null;
        await loadProfile();
    } catch (error) {
        if (error.response?.status === 422) {
            companyErrors.value = error.response.data.errors ?? {};
        }
    } finally {
        savingCompany.value = false;
    }
}

onMounted(loadProfile);
</script>

<template>
    <div v-if="loading" class="text-center py-5 text-muted">
        جاري التحميل...
    </div>

    <div v-else class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">البيانات الشخصية</h4>
                    <p class="text-muted mb-4">عدّل معلومات حسابك وصورتك الشخصية</p>

                    <form @submit.prevent="saveUserProfile">
                        <AvatarUploader
                            label="الصورة الشخصية"
                            :name="userForm.name"
                            :preview-url="userAvatarPreview"
                            @change="onUserAvatarChange"
                        />

                        <div class="mb-3">
                            <label class="form-label">الاسم</label>
                            <input v-model="userForm.name" type="text" class="form-control" maxlength="15" required>
                            <p v-if="!fieldError('name')" class="text-muted small mt-1 mb-0">لا يزيد عن 15 حرف</p>
                            <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input v-model="userForm.email" type="email" class="form-control" required>
                            <p v-if="!fieldError('email')" class="text-muted small mt-1 mb-0">مطلوب — بريد تسجيل الدخول</p>
                            <div v-if="fieldError('email')" class="text-danger small mt-1">{{ fieldError('email') }}</div>
                        </div>

                        <div class="mb-3">
                            <PasswordInput
                                v-model="userForm.password"
                                label="كلمة مرور جديدة (اختياري)"
                                hint="لا تقل عن 8 حروف وأرقام — اتركها فارغة إن لم تُرد التغيير"
                                :invalid="Boolean(fieldError('password'))"
                                :error="fieldError('password')"
                            />
                        </div>

                        <div class="mb-3">
                            <PasswordInput
                                v-model="userForm.password_confirmation"
                                label="تأكيد كلمة المرور"
                                hint="يجب أن يطابق كلمة المرور الجديدة"
                            />
                        </div>

                        <button type="submit" class="btn btn-primary" :disabled="savingUser">
                            {{ savingUser ? 'جاري الحفظ...' : 'حفظ البيانات الشخصية' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="canManageCompany && isCompanyAdmin" class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">بيانات الشركة</h4>
                    <p class="text-muted mb-4">متاحة لمدير الشركة فقط</p>

                    <form @submit.prevent="saveCompanyProfile">
                        <AvatarUploader
                            label="شعار الشركة"
                            :name="companyForm.name"
                            :preview-url="companyLogoPreview"
                            shape="rounded"
                            @change="onCompanyLogoChange"
                        />

                        <div class="mb-3">
                            <label class="form-label">اسم الشركة</label>
                            <input v-model="companyForm.name" type="text" class="form-control" maxlength="255" required>
                            <p v-if="!fieldError('name', 'company')" class="text-muted small mt-1 mb-0">مطلوب — الاسم الرسمي للشركة</p>
                            <div v-if="fieldError('name', 'company')" class="text-danger small mt-1">
                                {{ fieldError('name', 'company') }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">بريد الشركة</label>
                            <input v-model="companyForm.email" type="email" class="form-control" required>
                            <p v-if="!fieldError('email', 'company')" class="text-muted small mt-1 mb-0">مطلوب — بريد التواصل الرسمي للشركة</p>
                            <div v-if="fieldError('email', 'company')" class="text-danger small mt-1">
                                {{ fieldError('email', 'company') }}
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الدولة</label>
                                <input v-model="companyForm.country" type="text" class="form-control" maxlength="255">
                                <p v-if="!fieldError('country', 'company')" class="text-muted small mt-1 mb-0">اختياري</p>
                                <div v-if="fieldError('country', 'company')" class="text-danger small mt-1">
                                    {{ fieldError('country', 'company') }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">المدينة</label>
                                <input v-model="companyForm.city" type="text" class="form-control" maxlength="255">
                                <p v-if="!fieldError('city', 'company')" class="text-muted small mt-1 mb-0">اختياري</p>
                                <div v-if="fieldError('city', 'company')" class="text-danger small mt-1">
                                    {{ fieldError('city', 'company') }}
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">الهاتف</label>
                                <input v-model="companyForm.phone" type="text" class="form-control" maxlength="50">
                                <p v-if="!fieldError('phone', 'company')" class="text-muted small mt-1 mb-0">اختياري — رقم تواصل الشركة</p>
                                <div v-if="fieldError('phone', 'company')" class="text-danger small mt-1">
                                    {{ fieldError('phone', 'company') }}
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4" :disabled="savingCompany">
                            {{ savingCompany ? 'جاري الحفظ...' : 'حفظ بيانات الشركة' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
