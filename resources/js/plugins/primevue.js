import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';

export function setupPrimeVue(app) {
    app.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                prefix: 'p',
                darkModeSelector: '.app-dark',
            },
        },
        zIndex: {
            modal: 1100,
            overlay: 1200,
            menu: 1000,
            tooltip: 1300,
        },
        locale: {
            accept: 'موافق',
            reject: 'إلغاء',
            choose: 'اختر',
            upload: 'رفع',
            cancel: 'إلغاء',
            emptyMessage: 'لا توجد نتائج',
            emptyFilterMessage: 'لا توجد نتائج',
        },
    });

    app.component('PrimeMultiSelect', MultiSelect);
    app.component('PrimeSelect', Select);
    app.component('PrimeCheckbox', Checkbox);
}
