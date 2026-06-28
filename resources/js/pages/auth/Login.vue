<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/composables/useSkoteAssets';
import { DEMO_ACCOUNTS, SHOW_DEMO_HELPERS } from '@/utils/constants';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = ref({});
const errorMessage = ref('');
const showPassword = ref(false);

async function handleSubmit() {
    errors.value = {};
    errorMessage.value = '';

    try {
        await authStore.login(form);
        await authStore.fetchUser();

        if (!authStore.isEmailVerified) {
            router.push({ name: 'verify-code' });
            return;
        }

        const defaultRoute = authStore.isAdmin
            ? { name: 'admin.dashboard' }
            : { name: 'error-403' };

        const redirect = route.query.redirect ?? defaultRoute;
        router.push(redirect);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر تسجيل الدخول. حاول مرة أخرى.';
        }
    }
}

function togglePassword() {
    showPassword.value = !showPassword.value;
}

function fillDemoAccount(type) {
    const account = DEMO_ACCOUNTS[type];

    if (!account) {
        return;
    }

    form.email = account.email;
    form.password = account.password;
    errors.value = {};
    errorMessage.value = '';
}
</script>

<template>
    <div class="card overflow-hidden">
        <div class="bg-primary bg-soft">
            <div class="row">
                <div class="col-7">
                    <div class="text-primary p-4">
                        <h5 class="text-primary">مرحباً بعودتك!</h5>
                        <p>سجّل الدخول للمتابعة.</p>
                    </div>
                </div>
                <div class="col-5 align-self-end">
                    <img :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="auth-logo">
                <router-link :to="{ name: 'login' }" class="auth-logo-light">
                    <div class="avatar-md profile-user-wid mb-4">
                        <span class="avatar-title rounded-circle bg-light">
                            <img :src="asset('images/logo-light.svg')" alt="" class="rounded-circle" height="34">
                        </span>
                    </div>
                </router-link>

                <router-link :to="{ name: 'login' }" class="auth-logo-dark">
                    <div class="avatar-md profile-user-wid mb-4">
                        <span class="avatar-title rounded-circle bg-light">
                            <img :src="asset('images/logo.svg')" alt="" class="rounded-circle" height="34">
                        </span>
                    </div>
                </router-link>
            </div>

            <div class="p-2">
                <form class="form-horizontal" @submit.prevent="handleSubmit">
                    <div
                        v-if="errorMessage"
                        class="alert alert-danger"
                        role="alert"
                    >
                        {{ errorMessage }}
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': errors.email }"
                            placeholder="أدخل البريد الإلكتروني"
                            required
                            autocomplete="email"
                        >
                        <div v-if="errors.email" class="invalid-feedback d-block">
                            {{ errors.email[0] }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <div class="input-group auth-pass-inputgroup">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control"
                                :class="{ 'is-invalid': errors.password }"
                                placeholder="أدخل كلمة المرور"
                                aria-label="Password"
                                aria-describedby="password-addon"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                class="btn btn-light"
                                type="button"
                                id="password-addon"
                                @click="togglePassword"
                            >
                                <i :class="showPassword ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline'"></i>
                            </button>
                        </div>
                        <div v-if="errors.password" class="invalid-feedback d-block">
                            {{ errors.password[0] }}
                        </div>
                    </div>

                    <div class="form-check">
                        <input
                            id="remember-check"
                            v-model="form.remember"
                            class="form-check-input"
                            type="checkbox"
                        >
                        <label class="form-check-label" for="remember-check">
                            تذكرني
                        </label>
                    </div>

                    <div v-if="SHOW_DEMO_HELPERS" class="demo-accounts mb-4">
                        <div class="demo-accounts-header text-center">
                            <span class="badge rounded-pill bg-soft-warning text-warning mb-2">
                                للتجربة فقط
                            </span>
                            <p class="demo-accounts-title mb-1">دخول سريع</p>
                            <p class="demo-accounts-subtitle mb-0">اضغط لتعبئة البريد وكلمة المرور</p>
                        </div>

                        <div class="row g-2 mt-2">
                            <div
                                v-for="(account, key) in DEMO_ACCOUNTS"
                                :key="key"
                                class="col-6"
                            >
                                <button
                                    type="button"
                                    class="demo-account-btn w-100"
                                    :class="`demo-account-btn--${account.variant}`"
                                    @click="fillDemoAccount(key)"
                                >
                                    <span class="demo-account-btn__icon">
                                        <i :class="['bx', account.icon]"></i>
                                    </span>
                                    <span class="demo-account-btn__title">{{ account.buttonTitle }}</span>
                                    <span class="demo-account-btn__email">{{ account.email }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-grid">
                        <button
                            class="btn btn-primary waves-effect waves-light"
                            type="submit"
                            :disabled="authStore.loading"
                        >
                            {{ authStore.loading ? 'جاري تسجيل الدخول...' : 'تسجيل الدخول' }}
                        </button>
                    </div>

                    <div class="mt-4 text-center">
                        <router-link :to="{ name: 'forgot-password' }" class="text-muted">
                            <i class="mdi mdi-lock me-1"></i>
                            نسيت كلمة المرور؟
                        </router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <p>
            ليس لديك حساب؟
            <router-link :to="{ name: 'register' }" class="fw-medium text-primary">
                سجل شركة الان
            </router-link>
        </p>
    </div>
</template>

<style scoped>
.demo-accounts {
    padding: 1rem;
    border: 1px dashed rgba(85, 110, 230, 0.35);
    border-radius: 0.5rem;
    background: rgba(85, 110, 230, 0.06);
}

.demo-accounts-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: inherit;
}

.demo-accounts-subtitle {
    font-size: 0.78rem;
    color: #a6b0cf;
}

.demo-account-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 6.5rem;
    padding: 0.85rem 0.5rem;
    border: 1px solid transparent;
    border-radius: 0.5rem;
    background: transparent;
    transition: all 0.2s ease;
    text-align: center;
}

.demo-account-btn__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    font-size: 1.15rem;
}

.demo-account-btn__title {
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.3;
}

.demo-account-btn__email {
    font-size: 0.68rem;
    line-height: 1.2;
    opacity: 0.85;
    word-break: break-all;
}

.demo-account-btn--super {
    border-color: rgba(85, 110, 230, 0.45);
    color: #556ee6;
}

.demo-account-btn--super .demo-account-btn__icon {
    background: rgba(85, 110, 230, 0.15);
}

.demo-account-btn--super:hover {
    background: rgba(85, 110, 230, 0.12);
    border-color: #556ee6;
    color: #556ee6;
}

.demo-account-btn--company {
    border-color: rgba(80, 165, 241, 0.45);
    color: #50a5f1;
}

.demo-account-btn--company .demo-account-btn__icon {
    background: rgba(80, 165, 241, 0.15);
}

.demo-account-btn--company:hover {
    background: rgba(80, 165, 241, 0.12);
    border-color: #50a5f1;
    color: #50a5f1;
}
</style>
