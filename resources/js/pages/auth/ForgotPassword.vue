<script setup>
import { reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/composables/useSkoteAssets';

const authStore = useAuthStore();

const form = reactive({
    email: '',
});

const errors = ref({});
const successMessage = ref('');
const errorMessage = ref('');

async function handleSubmit() {
    errors.value = {};
    successMessage.value = '';
    errorMessage.value = '';

    try {
        const data = await authStore.forgotPassword(form);
        successMessage.value = data.message ?? 'تم إرسال رابط إعادة التعيين إلى بريدك.';
        form.email = '';
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors ?? {};
        } else {
            errorMessage.value = error.response?.data?.message ?? 'تعذر إرسال رابط إعادة التعيين. حاول مرة أخرى.';
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
                        <h5 class="text-primary">نسيت كلمة المرور؟</h5>
                        <p>أدخل بريدك وسنرسل لك رابط إعادة التعيين.</p>
                    </div>
                </div>
                <div class="col-5 align-self-end">
                    <img :src="asset('images/profile-img.png')" alt="" class="img-fluid">
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="p-2 pt-4">
                <form @submit.prevent="handleSubmit">
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

                    <div class="mb-4">
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
                            {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
                        </div>
                    </div>

                    <div class="d-grid">
                        <button
                            class="btn btn-primary waves-effect waves-light"
                            type="submit"
                            :disabled="authStore.loading"
                        >
                            {{ authStore.loading ? 'جاري الإرسال...' : 'إرسال رابط إعادة التعيين' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <router-link :to="{ name: 'login' }" class="fw-medium text-primary">
            <i class="mdi mdi-arrow-right me-1"></i>
            العودة لتسجيل الدخول
        </router-link>
    </div>
</template>
