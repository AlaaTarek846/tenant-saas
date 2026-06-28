const BASE = '/dashboard/assets';

const loadedScripts = new Set();
let currentTheme = null;

function loadStyle(href) {
    const existing = document.querySelector(`link[rel="stylesheet"][href="${href}"]`);

    if (existing) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.setAttribute('data-skote-theme', 'skote-rtl');
        link.onload = () => resolve();
        link.onerror = reject;
        document.head.appendChild(link);
    });
}

function loadScript(src) {
    if (loadedScripts.has(src)) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => {
            loadedScripts.add(src);
            resolve();
        };
        script.onerror = reject;
        document.body.appendChild(script);
    });
}

export function asset(path) {
    return `${BASE}/${path.replace(/^\//, '')}`;
}

async function loadSharedScripts() {
    await loadScript(asset('libs/jquery/jquery.min.js'));
    await loadScript(asset('libs/bootstrap/js/bootstrap.bundle.min.js'));
    await loadScript(asset('libs/metismenu/metisMenu.min.js'));
    await loadScript(asset('libs/simplebar/simplebar.min.js'));
    await loadScript(asset('libs/node-waves/waves.min.js'));

    if (window.Waves) {
        window.Waves.init();
    }
}

async function loadSkoteRtlStyles() {
    await Promise.all([
        loadStyle(asset('css/bootstrap-dark-rtl.min.css')),
        loadStyle(asset('css/icons.min.css')),
        loadStyle(asset('css/app-dark-rtl.min.css')),
    ]);
}

function applyThemeMode(mode) {
    document.documentElement.setAttribute('dir', 'rtl');
    document.documentElement.setAttribute('lang', 'ar');

    if (mode === 'dashboard') {
        document.body.setAttribute('data-sidebar', 'dark');
        document.body.classList.remove('account-pages');
    } else {
        document.body.removeAttribute('data-sidebar');
        document.body.classList.add('account-pages');
    }
}

async function loadSkoteTheme(mode) {
    if (currentTheme === mode) {
        return;
    }

    currentTheme = mode;
    applyThemeMode(mode);

    await loadSkoteRtlStyles();
    await loadSharedScripts();
}

export async function loadDashboardTheme() {
    await loadSkoteTheme('dashboard');
}

export async function loadAuthTheme() {
    await loadSkoteTheme('auth');
}

export function initMetisMenu() {
    const $ = window.jQuery;

    if (!$ || !$('#side-menu').length) {
        return;
    }

    try {
        $('#side-menu').metisMenu('dispose');
    } catch {
        // metisMenu may not be initialized yet
    }

    $('#side-menu').metisMenu();
}

export function toggleSidebar() {
    document.body.classList.toggle('sidebar-enable');

    if (window.innerWidth >= 992) {
        document.body.classList.toggle('vertical-collpsed');
    } else {
        document.body.classList.remove('vertical-collpsed');
    }
}
