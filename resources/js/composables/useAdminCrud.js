import { ref } from 'vue';
import api from '@/services/api';
import { useConfirmStore } from '@/stores/confirm';
import {
    CRUD_MESSAGES,
    getErrorMessage,
    getValidationErrors,
    notifyError,
    notifySuccess,
    unwrapApiData,
    unwrapApiMessage,
} from '@/utils/apiHandler';

export function useAdminCrud(baseUrl, options = {}) {
    const {
        resourceLabel = 'العنصر',
        notifyOnFetchError = false,
    } = options;

    const items = ref([]);
    const pagination = ref(null);
    const loading = ref(false);
    const saving = ref(false);
    const errorMessage = ref('');
    const errors = ref({});

    async function fetchList(params = {}) {
        loading.value = true;
        errorMessage.value = '';

        try {
            const response = await api.get(baseUrl, { params });
            const data = unwrapApiData(response);

            if (Array.isArray(data)) {
                items.value = data;
                pagination.value = response.data?.pagination ?? null;
            } else {
                items.value = data?.data ?? data ?? [];
                pagination.value = response.data?.pagination ?? data?.paginate ?? null;
            }

            return items.value;
        } catch (error) {
            errorMessage.value = getErrorMessage(error, 'تعذر تحميل البيانات.');

            if (notifyOnFetchError) {
                notifyError(error, 'تعذر تحميل البيانات.');
            }

            throw error;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOne(id) {
        const response = await api.get(`${baseUrl}/${id}`);
        return unwrapApiData(response);
    }

    async function create(payload) {
        saving.value = true;
        errors.value = {};
        errorMessage.value = '';

        try {
            const response = await api.post(baseUrl, payload);
            notifySuccess(response, CRUD_MESSAGES.created);
            return unwrapApiData(response);
        } catch (error) {
            handleError(error);
            throw error;
        } finally {
            saving.value = false;
        }
    }

    async function update(id, payload) {
        saving.value = true;
        errors.value = {};
        errorMessage.value = '';

        try {
            const response = await api.put(`${baseUrl}/${id}`, payload);
            notifySuccess(response, CRUD_MESSAGES.updated);
            return unwrapApiData(response);
        } catch (error) {
            handleError(error);
            throw error;
        } finally {
            saving.value = false;
        }
    }

    async function remove(id) {
        saving.value = true;
        errorMessage.value = '';

        try {
            const response = await api.delete(`${baseUrl}/${id}`);
            notifySuccess(response, CRUD_MESSAGES.deleted);
            return unwrapApiMessage(response);
        } catch (error) {
            throw error;
        } finally {
            saving.value = false;
        }
    }

    async function removeConfirmed(id, confirmOptions = {}) {
        const confirmStore = useConfirmStore();
        const label = confirmOptions.label ?? confirmOptions.name ?? '';

        return confirmStore.ask({
            title: confirmOptions.title ?? `حذف ${resourceLabel}`,
            message: confirmOptions.message ?? (label ? `هل تريد حذف «${label}»؟` : 'هل تريد الحذف؟'),
            confirmLabel: confirmOptions.confirmLabel ?? 'حذف',
            onConfirm: () => remove(id),
        });
    }

    function handleError(error) {
        if (error.response?.status === 422) {
            errors.value = getValidationErrors(error);
            errorMessage.value = error.response?.data?.message ?? '';
        } else {
            errorMessage.value = getErrorMessage(error, 'حدث خطأ غير متوقع.');
        }
    }

    return {
        items,
        pagination,
        loading,
        saving,
        errorMessage,
        errors,
        fetchList,
        fetchOne,
        create,
        update,
        remove,
        removeConfirmed,
    };
}
