/**
 * Frontend mirror of app/Helpers/ApiHandler.php response format.
 * Use notifySuccess / notifyError with unwrapApiData across the admin UI.
 */
import { useToastStore } from '@/stores/toast';
import { unwrapApiData, unwrapApiMessage } from '@/utils/apiResponse';

export { unwrapApiData, unwrapApiMessage };

export function isApiSuccess(response) {
    const body = response?.data;

    return body?.status === 'success' || body?.code === true;
}

export function getApiMessage(response) {
    return response?.data?.message ?? '';
}

export function getErrorMessage(error, fallback = 'حدث خطأ غير متوقع.') {
    const body = error?.response?.data;

    if (body?.message) {
        return body.message;
    }

    if (error?.response?.status === 500) {
        return 'خطأ في الخادم (500). حاول مرة أخرى أو تواصل مع الدعم.';
    }

    if (error?.response?.status === 409) {
        return body?.message ?? 'لا يمكن الحذف لوجود بيانات مرتبطة.';
    }

    if (error?.response?.status === 403) {
        return 'ليس لديك صلاحية لتنفيذ هذا الإجراء.';
    }

    if (error?.response?.status === 404) {
        return 'العنصر المطلوب غير موجود.';
    }

    return fallback;
}

export function getValidationErrors(error) {
    if (error?.response?.status !== 422) {
        return {};
    }

    const body = error.response.data;

    return body?.errors ?? {};
}

export function notifySuccess(response, fallback = 'تمت العملية بنجاح.') {
    const toast = useToastStore();
    toast.success(getApiMessage(response) || fallback);
}

export function notifyError(error, fallback = 'حدث خطأ غير متوقع.') {
    const toast = useToastStore();
    toast.error(getErrorMessage(error, fallback));
}

export function notifyInfo(message) {
    const toast = useToastStore();
    toast.info(message);
}

export const CRUD_MESSAGES = {
    created: 'تمت الإضافة بنجاح.',
    updated: 'تم التحديث بنجاح.',
    deleted: 'تم الحذف بنجاح.',
    loaded: 'تم تحميل البيانات.',
};
