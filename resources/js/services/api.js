import axios from 'axios';

const baseURL = import.meta.env.DEV
    ? ''
    : (import.meta.env.VITE_APP_URL ?? window.location.origin);

const api = axios.create({
    baseURL,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export default api;
