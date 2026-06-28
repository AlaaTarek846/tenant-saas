import { createPinia } from 'pinia';

export const pinia = createPinia();

export { useAuthStore } from '@/stores/auth';
export { useAppStore } from '@/stores/app';
