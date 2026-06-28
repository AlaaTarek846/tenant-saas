import { useConfirmStore } from '@/stores/confirm';

export function useConfirmDelete() {
    const confirmStore = useConfirmStore();

    function confirmDelete(options = {}) {
        const {
            title = 'تأكيد الحذف',
            message = 'هل تريد الحذف؟',
            confirmLabel = 'حذف',
            onConfirm,
        } = options;

        return confirmStore.ask({
            title,
            message,
            confirmLabel,
            onConfirm,
        });
    }

    return {
        confirmDelete,
    };
}
