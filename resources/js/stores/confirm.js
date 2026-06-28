import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useConfirmStore = defineStore('confirm', () => {
    const visible = ref(false);
    const title = ref('تأكيد الحذف');
    const message = ref('هل تريد الحذف؟');
    const confirmLabel = ref('حذف');
    const loading = ref(false);

    let pendingResolve = null;
    let pendingAction = null;

    function ask(options = {}) {
        title.value = options.title ?? 'تأكيد الحذف';
        message.value = options.message ?? 'هل تريد الحذف؟';
        confirmLabel.value = options.confirmLabel ?? 'حذف';
        pendingAction = options.onConfirm ?? null;
        visible.value = true;

        return new Promise((resolve) => {
            pendingResolve = resolve;
        });
    }

    function close(result = false) {
        visible.value = false;
        loading.value = false;
        pendingAction = null;

        if (pendingResolve) {
            pendingResolve(result);
            pendingResolve = null;
        }
    }

    async function confirm() {
        if (loading.value) {
            return;
        }

        loading.value = true;

        try {
            if (pendingAction) {
                await pendingAction();
            }

            close(true);
        } catch {
            close(false);
        }
    }

    function cancel() {
        close(false);
    }

    return {
        visible,
        title,
        message,
        confirmLabel,
        loading,
        ask,
        confirm,
        cancel,
    };
});
