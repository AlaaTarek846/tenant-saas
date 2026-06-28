import './bootstrap';
import { createApp } from 'vue';
import App from '@/App.vue';
import router from '@/router';
import { pinia } from '@/stores';
import { setupHttpInterceptors } from '@/plugins/http';
import { setupPrimeVue } from '@/plugins/primevue';
import 'primeicons/primeicons.css';
import '../css/primevue-overrides.css';

const app = createApp(App);

app.use(pinia);
app.use(router);
setupPrimeVue(app);

setupHttpInterceptors(router);

app.mount('#app');
