<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="shortcut icon" href="{{ asset('dashboard/assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap-dark-rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/app-dark-rtl.min.css') }}">

    <style>
        #app:not(.app-ready) {
            visibility: hidden;
        }

        #app-boot-loader {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        #app-boot-loader .boot-spinner {
            width: 2.75rem;
            height: 2.75rem;
            border: 0.25rem solid rgba(85, 110, 230, 0.2);
            border-top-color: #556ee6;
            border-radius: 50%;
            animation: boot-spin 0.75s linear infinite;
        }

        #app-boot-loader .boot-text {
            margin-top: 1rem;
            color: #495057;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 0.95rem;
            text-align: center;
        }

        @keyframes boot-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app-boot-loader" aria-live="polite" aria-busy="true">
        <div>
            <div class="boot-spinner" role="status"></div>
            <p class="boot-text">جاري تحميل التطبيق...</p>
        </div>
    </div>
    <div id="app"></div>
</body>
</html>
