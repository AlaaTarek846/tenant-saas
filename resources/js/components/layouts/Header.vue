<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import InitialsAvatar from '@/components/admin/InitialsAvatar.vue';
import { asset, toggleSidebar } from '@/composables/useSkoteAssets';
import { userAvatarUrl } from '@/utils/userAvatar';
import { getNameInitials } from '@/utils/initials';

defineProps({
    homeRoute: {
        type: String,
        default: 'admin.dashboard',
    },
});

const router = useRouter();
const authStore = useAuthStore();

const userName = computed(() => authStore.user?.name ?? 'User');
const avatarSrc = computed(() => userAvatarUrl(authStore.user));
const company = computed(() => authStore.user?.company ?? null);
const hasCompany = computed(() => Boolean(company.value));
const companyLogoUrl = computed(() => company.value?.logo_url ?? null);
const companyName = computed(() => company.value?.name ?? '');
const companyInitial = computed(() => getNameInitials(companyName.value));

function goToProfile() {
    router.push({ name: 'admin.profile' });
}

async function handleLogout() {
    await authStore.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <header id="page-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <div class="navbar-brand-box">
                    <template v-if="hasCompany && companyLogoUrl">
                        <router-link :to="{ name: homeRoute }" class="logo logo-dark">
                            <span class="logo-sm">
                                <img :src="companyLogoUrl" alt="" class="company-brand-logo" height="22">
                            </span>
                            <span class="logo-lg">
                                <img :src="companyLogoUrl" alt="" class="company-brand-logo-lg" height="28">
                            </span>
                        </router-link>

                        <router-link :to="{ name: homeRoute }" class="logo logo-light">
                            <span class="logo-sm">
                                <img :src="companyLogoUrl" alt="" class="company-brand-logo" height="22">
                            </span>
                            <span class="logo-lg">
                                <img :src="companyLogoUrl" alt="" class="company-brand-logo-lg" height="28">
                            </span>
                        </router-link>
                    </template>

                    <template v-else-if="hasCompany">
                        <router-link :to="{ name: homeRoute }" class="logo logo-dark">
                            <span class="logo-sm">
                                <span class="company-brand-initial">{{ companyInitial }}</span>
                            </span>
                            <span class="logo-lg">
                                <span class="company-brand-name">{{ companyName }}</span>
                            </span>
                        </router-link>

                        <router-link :to="{ name: homeRoute }" class="logo logo-light">
                            <span class="logo-sm">
                                <span class="company-brand-initial company-brand-initial-light">{{ companyInitial }}</span>
                            </span>
                            <span class="logo-lg">
                                <span class="company-brand-name company-brand-name-light">{{ companyName }}</span>
                            </span>
                        </router-link>
                    </template>

                    <template v-else>
                        <router-link :to="{ name: homeRoute }" class="logo logo-dark">
                            <span class="logo-sm">
                                <img :src="asset('images/logo.svg')" alt="" height="22">
                            </span>
                            <span class="logo-lg">
                                <img :src="asset('images/logo-dark.png')" alt="" height="17">
                            </span>
                        </router-link>

                        <router-link :to="{ name: homeRoute }" class="logo logo-light">
                            <span class="logo-sm">
                                <img :src="asset('images/logo-light.svg')" alt="" height="22">
                            </span>
                            <span class="logo-lg">
                                <img :src="asset('images/logo-light.png')" alt="" height="19">
                            </span>
                        </router-link>
                    </template>
                </div>

                <button
                    type="button"
                    class="btn btn-sm px-3 font-size-16 header-item waves-effect"
                    id="vertical-menu-btn"
                    @click="toggleSidebar"
                >
                    <i class="fa fa-fw fa-bars"></i>
                </button>

                <form class="app-search d-none d-lg-block">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="بحث...">
                        <span class="bx bx-search-alt"></span>
                    </div>
                </form>
            </div>

            <div class="d-flex">
                <div class="dropdown d-inline-block">
                    <button
                        type="button"
                        class="btn header-item waves-effect"
                        id="page-header-user-dropdown"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <InitialsAvatar
                            class="header-profile-user"
                            :name="userName"
                            :image-url="avatarSrc"
                            shape="circle"
                            :size="36"
                            font-size="0.8rem"
                        />
                        <span class="d-none d-xl-inline-block ms-1">{{ userName }}</span>
                        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                        <button
                            type="button"
                            class="dropdown-item"
                            @click="goToProfile"
                        >
                            <i class="bx bx-user-circle font-size-16 align-middle me-1"></i>
                            الملف الشخصي
                        </button>
                        <div class="dropdown-divider"></div>
                        <span class="dropdown-item-text text-muted">{{ authStore.user?.email }}</span>
                        <div class="dropdown-divider"></div>
                        <button
                            type="button"
                            class="dropdown-item text-danger"
                            :disabled="authStore.loading"
                            @click="handleLogout"
                        >
                            <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i>
                            تسجيل الخروج
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.company-brand-logo,
.company-brand-logo-lg {
    object-fit: contain;
    max-width: 100%;
}

.company-brand-initial {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: rgba(85, 110, 230, 0.15);
    color: #556ee6;
    font-size: 0.85rem;
    font-weight: 700;
    line-height: 1;
}

.company-brand-initial-light {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.company-brand-name {
    display: inline-block;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 1rem;
    font-weight: 600;
    color: #556ee6;
    vertical-align: middle;
}

.company-brand-name-light {
    color: #fff;
}
</style>
