<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import PasswordInput from '@/components/admin/PasswordInput.vue';
import { asset } from '@/composables/useSkoteAssets';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const errorMessage = ref('');
const successMessage = ref('');
const invalidLink = ref(false);

onMounted(() => {
    form.token = String(route.query.token ?? '');
    form.email = String(route.query.email ?? '');

    if (!form.token || !form.email) {
        invalidLink.value = true;
    }
});

async function handleSubmit() {
    errors.value = {};
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const data = await authStore.resetPassword({ ...form });
        successMessage.value = data.message ?? 'تم تغيير كلمة المرور بنجاح.';

        setTimeout(() => {
            router.push({ name: 'login' });
        }, 2000);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر إعادة تعيين كلمة المرور. حاول مرة أخرى.';
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
                        <h5 class="text-primary">كلمة مرور جديدة</h5>
                        <p>أدخل كلمة المرور الجديدة لحسابك.</p>
                    </div>
                </div>
                <div class="col-5 align-self-end">
                    <img :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="p-2 pt-4">
                <div v-if="invalidLink" class="alert alert-warning" role="alert">
                    رابط إعادة التعيين غير صالح. اطلب رابطاً جديداً من صفحة نسيت كلمة المرور.
                </div>

                <form v-else @submit.prevent="handleSubmit">
                    <div
                        v-if="successMessage"
                        class="alert alert-success"
                        role="alert"
                    >
                        {{ successMessage }}
                    </div>

                    <div
                        v-if="errorMessage"
                        class="alert alert-danger"
                        role="alert"
                    >
                        {{ errorMessage }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            readonly
                        >
                    </div>

                    <div class="mb-3">
                        <PasswordInput
                            v-model="form.password"
                            label="كلمة المرور الجديدة"
                            hint="لا تقل عن 8 حروف وأرقام"
                            :invalid="Boolean(errors.password)"
                            :error="Array.isArray(errors.password) ? errors.password[0] : errors.password"
                            required
                        />
                    </div>

                    <div class="mb-4">
                        <PasswordInput
                            v-model="form.password_confirmation"
                            label="تأكيد كلمة المرور"
                            required
                        />
                    </div>

                    <div class="d-grid">
                        <button
                            class="btn btn-primary waves-effect waves-light"
                            type="submit"
                            :disabled="authStore.loading || Boolean(successMessage)"
                        >
                            {{ authStore.loading ? 'جاري الحفظ...' : 'حفظ كلمة المرور' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <router-link :to="{ name: 'login' }" class="fw-medium text-primary">
            العودة لتسجيل الدخول
        </router-link>
    </div>
</template>
