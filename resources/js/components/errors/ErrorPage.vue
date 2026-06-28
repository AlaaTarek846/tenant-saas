<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { asset } from '@/composables/useSkoteAssets';

const props = defineProps({
    code: {
        type: String,
        required: true,
    },
    middleIcon: {
        type: String,
        default: 'bx-buoy bx-spin',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    primaryLabel: {
        type: String,
        default: 'العودة للوحة التحكم',
    },
    primaryAction: {
        type: String,
        default: 'home',
        validator: (value) => ['home', 'back', 'login'].includes(value),
    },
    image: {
        type: String,
        default: 'images/maintenance.svg',
    },
});

const router = useRouter();
const authStore = useAuthStore();

const codeParts = computed(() => ({
    first: props.code.charAt(0),
    last: props.code.charAt(props.code.length - 1),
}));

function handlePrimaryAction() {
    if (props.primaryAction === 'back') {
        router.back();
        return;
    }

    if (props.primaryAction === 'login') {
        router.push({ name: 'login' });
        return;
    }

    if (authStore.isAuthenticated && authStore.isAdmin) {
        router.push({ name: 'admin.dashboard' });
        return;
    }

    if (authStore.isAuthenticated) {
        router.push({ name: 'error-403' });
        return;
    }

    router.push({ name: 'login' });
}
</script>

<template>
    <div class="row">
        <div class="col-lg-12">
            <div class="text-center mb-5">
                <h1 class="display-2 fw-medium error-code">
                    {{ codeParts.first }}
                    <i :class="['bx', middleIcon, 'text-primary', 'display-3']"></i>
                    {{ codeParts.last }}
                </h1>
                <h4 class="text-uppercase">{{ title }}</h4>
                <p v-if="description" class="text-muted mt-3 mb-0 mx-auto error-description">
                    {{ description }}
                </p>
                <div class="mt-4 text-center">
                    <button
                        type="button"
                        class="btn btn-primary waves-effect waves-light"
                        @click="handlePrimaryAction"
                    >
                        {{ primaryLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-6">
            <img :src="asset(image)" alt="" class="img-fluid d-block mx-auto">
        </div>
    </div>
</template>

<style scoped>
.error-code {
    line-height: 1.1;
}

.error-description {
    max-width: 32rem;
}
</style>
