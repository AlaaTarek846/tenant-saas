<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import PasswordInput from '@/components/admin/PasswordInput.vue';
import { asset } from '@/composables/useSkoteAssets';

const router = useRouter();
const authStore = useAuthStore();

const step = ref(1);

const form = reactive({
    company: {
        name: '',
        email: '',
        country: '',
        city: '',
        phone: '',
    },
    admin: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    },
});

const errors = ref({});
const errorMessage = ref('');

function fieldError(...keys) {
    for (const key of keys) {
        const value = errors.value?.[key];

        if (value) {
            return Array.isArray(value) ? value[0] : value;
        }
    }

    return null;
}

function validateCompanyStep() {
    errors.value = {};
    errorMessage.value = '';

    const required = [
        ['company.name', form.company.name.trim(), 'اسم الشركة مطلوب.'],
        ['company.email', form.company.email.trim(), 'بريد الشركة مطلوب.'],
    ];

    for (const [key, value, message] of required) {
        if (!value) {
            errors.value[key] = [message];
            return false;
        }
    }

    return true;
}

function goToAdminStep() {
    if (validateCompanyStep()) {
        step.value = 2;
    }
}

function goBackToCompanyStep() {
    step.value = 1;
    errors.value = {};
    errorMessage.value = '';
}

async function handleSubmit() {
    errors.value = {};
    errorMessage.value = '';

    try {
        await authStore.register({
            company: { ...form.company },
            admin: { ...form.admin },
        });
        router.push({ name: 'verify-code' });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};

            const companyFields = ['company.name', 'company.email', 'company.country', 'company.city', 'company.phone'];
            const hasCompanyError = companyFields.some((field) => errors.value[field]);

            if (hasCompanyError) {
                step.value = 1;
            }
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر تسجيل الشركة. حاول مرة أخرى.';
        }
    }
}
</script>

<template>
    <div class="card overflow-hidden">
        <div class="bg-primary bg-soft">
            <div class="row">
                <div class="col-7">
                    <div class="text-primary p-4">
                        <h5 class="text-primary">تسجيل شركة</h5>
                        <p>{{ step === 1 ? 'الخطوة 1: بيانات الشركة' : 'الخطوة 2: بيانات مدير الشركة' }}</p>
                    </div>
                </div>
                <div class="col-5 align-self-end">
                    <img :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="px-2 pt-3">
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge rounded-pill" :class="step === 1 ? 'bg-primary' : 'bg-soft-primary text-primary'">
                        1. الشركة
                    </span>
                    <span class="badge rounded-pill" :class="step === 2 ? 'bg-primary' : 'bg-soft-secondary text-muted'">
                        2. المدير
                    </span>
                </div>
            </div>

            <div class="p-2">
                <div
                    v-if="errorMessage"
                    class="alert alert-danger"
                    role="alert"
                >
                    {{ errorMessage }}
                </div>

                <form v-if="step === 1" @submit.prevent="goToAdminStep">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">اسم الشركة</label>
                        <input
                            id="company_name"
                            v-model="form.company.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': fieldError('company.name') }"
                            placeholder="أدخل اسم الشركة"
                            required
                        >
                        <div v-if="fieldError('company.name')" class="invalid-feedback d-block">
                            {{ fieldError('company.name') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="company_email" class="form-label">بريد الشركة</label>
                        <input
                            id="company_email"
                            v-model="form.company.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': fieldError('company.email') }"
                            placeholder="company@example.com"
                            required
                            autocomplete="organization"
                        >
                        <div v-if="fieldError('company.email')" class="invalid-feedback d-block">
                            {{ fieldError('company.email') }}
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="company_country" class="form-label">الدولة</label>
                            <input
                                id="company_country"
                                v-model="form.company.country"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': fieldError('company.country') }"
                                placeholder="مصر"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="company_city" class="form-label">المدينة</label>
                            <input
                                id="company_city"
                                v-model="form.company.city"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': fieldError('company.city') }"
                                placeholder="القاهرة"
                            >
                        </div>
                        <div class="col-12">
                            <label for="company_phone" class="form-label">الهاتف</label>
                            <input
                                id="company_phone"
                                v-model="form.company.phone"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': fieldError('company.phone') }"
                                placeholder="01xxxxxxxxx"
                            >
                        </div>
                    </div>

                    <div class="mt-4 d-grid">
                        <button class="btn btn-primary waves-effect waves-light" type="submit">
                            التالي
                        </button>
                    </div>
                </form>

                <form v-else @submit.prevent="handleSubmit">
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">اسم المدير</label>
                        <input
                            id="admin_name"
                            v-model="form.admin.name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': fieldError('admin.name') }"
                            placeholder="أدخل اسم المدير"
                            maxlength="15"
                            required
                            autocomplete="name"
                        >
                        <p v-if="!fieldError('admin.name')" class="text-muted small mt-1 mb-0">لا يزيد عن 15 حرف</p>
                        <div v-if="fieldError('admin.name')" class="invalid-feedback d-block">
                            {{ fieldError('admin.name') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="admin_email" class="form-label">بريد المدير</label>
                        <input
                            id="admin_email"
                            v-model="form.admin.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': fieldError('admin.email') }"
                            placeholder="admin@example.com"
                            required
                            autocomplete="email"
                        >
                        <div v-if="fieldError('admin.email')" class="invalid-feedback d-block">
                            {{ fieldError('admin.email') }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <PasswordInput
                            v-model="form.admin.password"
                            label="كلمة المرور"
                            placeholder="أدخل كلمة المرور"
                            hint="لا تقل عن 8 حروف وأرقام"
                            :invalid="Boolean(fieldError('admin.password'))"
                            :error="fieldError('admin.password')"
                            required
                        />
                    </div>

                    <div class="mb-3">
                        <PasswordInput
                            v-model="form.admin.password_confirmation"
                            label="تأكيد كلمة المرور"
                            placeholder="أعد إدخال كلمة المرور"
                            required
                        />
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <button
                            class="btn btn-primary waves-effect waves-light"
                            type="submit"
                            :disabled="authStore.loading"
                        >
                            {{ authStore.loading ? 'جاري التسجيل...' : 'تسجيل الشركة' }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-soft-secondary"
                            :disabled="authStore.loading"
                            @click="goBackToCompanyStep"
                        >
                            رجوع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <p>
            لديك حساب بالفعل؟
            <router-link :to="{ name: 'login' }" class="fw-medium text-primary">
                تسجيل الدخول
            </router-link>
        </p>
    </div>
</template>
