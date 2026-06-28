import { defineStore } from 'pinia';
import { ref } from 'vue';

let toastId = 0;

export const useToastStore = defineStore('toast', () => {
    const items = ref([]);

    function push(severity, summary, detail, life = 4000) {
        const id = ++toastId;

        items.value.push({
            id,
            severity,
            summary,
            detail,
            life,
        });

        if (life > 0) {
            setTimeout(() => dismiss(id), life);
        }

        return id;
    }

    function success(detail, summary = 'نجاح') {
        return push('success', summary, detail);
    }

    function error(detail, summary = 'خطأ') {
        return push('error', summary, detail, 6000);
    }

    function info(detail, summary = 'تنبيه') {
        return push('info', summary, detail);
    }

    function warn(detail, summary = 'تحذير') {
        return push('warn', summary, detail);
    }

    function dismiss(id) {
        items.value = items.value.filter((item) => item.id !== id);
    }

    function clear() {
        items.value = [];
    }

    return {
        items,
        success,
        error,
        info,
        warn,
        dismiss,
        clear,
    };
});
