<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/composables/useSkoteAssets';
import { DEMO_VERIFY_CODE, SHOW_DEMO_HELPERS } from '@/utils/constants';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    code: '',
});

const errors = ref({});
const errorMessage = ref('');
const successMessage = ref('');
const resendLoading = ref(false);
const cooldownSeconds = ref(0);

let cooldownTimer = null;

const userEmail = computed(() => authStore.user?.email ?? '');
const canResend = computed(() => cooldownSeconds.value <= 0);

function syncCooldown() {
    const fromUser = authStore.user?.verify_resend_available_in;

    if (typeof fromUser === 'number' && fromUser > 0) {
        cooldownSeconds.value = fromUser;
        return;
    }

    const expiresAt = authStore.user?.verify_code_expires_at;

    if (!expiresAt) {
        cooldownSeconds.value = 0;
        return;
    }

    const diff = Math.ceil((new Date(expiresAt).getTime() - Date.now()) / 1000);
    cooldownSeconds.value = Math.max(0, diff);
}

function startCooldownTimer() {
    clearInterval(cooldownTimer);
    syncCooldown();

    cooldownTimer = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value -= 1;
        } else {
            clearInterval(cooldownTimer);
        }
    }, 1000);
}

function redirectAfterVerify() {
    const route = authStore.isAdmin
        ? { name: 'admin.dashboard' }
        : { name: 'error-403' };

    router.push(route);
}

function useDemoCode() {
    form.code = DEMO_VERIFY_CODE;
    errors.value = {};
    errorMessage.value = '';
}

async function handleSubmit() {
    errors.value = {};
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await authStore.verifyCode(form.code);
        redirectAfterVerify();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر التحقق من الرمز. حاول مرة أخرى.';
        }
    }
}

async function handleResend() {
    if (!canResend.value || resendLoading.value) {
        return;
    }

    errors.value = {};
    errorMessage.value = '';
    successMessage.value = '';
    resendLoading.value = true;

    try {
        await authStore.resendVerifyCode();
        successMessage.value = 'تم إرسال رمز جديد. صالح لمدة دقيقة.';
        form.code = '';
        startCooldownTimer();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
            syncCooldown();
            startCooldownTimer();
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر إرسال الرمز. حاول لاحقاً.';
        }
    } finally {
        resendLoading.value = false;
    }
}

onMounted(() => {
    syncCooldown();
    startCooldownTimer();
});

onBeforeUnmount(() => {
    clearInterval(cooldownTimer);
});
</script>

<template>
    <div class="card overflow-hidden">
        <div class="bg-primary bg-soft">
            <div class="row">
                <div class="col-7">
                    <div class="text-primary p-4">
                        <h5 class="text-primary">تأكيد الحساب</h5>
                        <p>أدخل رمز التحقق المرسل إليك.</p>
                    </div>
                </div>
                <div class="col-5 align-self-end">
                    <img :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="p-2 pt-4">
                <p class="text-muted text-center mb-4">
                    تم إرسال رمز مكوّن من 6 أرقام إلى
                    <strong>{{ userEmail }}</strong>
                </p>

                <div
                    v-if="SHOW_DEMO_HELPERS"
                    class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4"
                    role="alert"
                >
                    <div>
                        <strong>رمز التجربة الثابت:</strong>
                        <span class="fs-5 fw-bold letter-spacing-wide ms-1">{{ DEMO_VERIFY_CODE }}</span>
                        <p class="mb-0 small mt-1 text-muted">استخدم هذا الرقم في بيئة التطوير</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="useDemoCode"
                    >
                        تعبئة الرمز
                    </button>
                </div>

                <form @submit.prevent="handleSubmit">
                    <div
                        v-if="errorMessage"
                        class="alert alert-danger"
                        role="alert"
                    >
                        {{ errorMessage }}
                    </div>

                    <div
                        v-if="successMessage"
                        class="alert alert-success"
                        role="alert"
                    >
                        {{ successMessage }}
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">رمز التحقق</label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            class="form-control text-center fs-4 letter-spacing-wide"
                            :class="{ 'is-invalid': errors.code }"
                            placeholder="000000"
                            required
                            autocomplete="one-time-code"
                        >
                        <div v-if="errors.code" class="invalid-feedback d-block">
                            {{ Array.isArray(errors.code) ? errors.code[0] : errors.code }}
                        </div>
                    </div>

                    <div class="mt-4 d-grid">
                        <button
                            class="btn btn-primary waves-effect waves-light"
                            type="submit"
                            :disabled="authStore.loading || form.code.length !== 6"
                        >
                            {{ authStore.loading ? 'جاري التحقق...' : 'تأكيد' }}
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <p v-if="!canResend" class="text-muted mb-2">
                        يمكنك طلب رمز جديد بعد
                        <strong>{{ cooldownSeconds }}</strong>
                        ثانية
                    </p>
                    <button
                        type="button"
                        class="btn btn-link text-primary p-0"
                        :disabled="!canResend || resendLoading"
                        @click="handleResend"
                    >
                        {{ resendLoading ? 'جاري الإرسال...' : 'إرسال رمز جديد' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.letter-spacing-wide {
    letter-spacing: 0.35rem;
}
</style>
