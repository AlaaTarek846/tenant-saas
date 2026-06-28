<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Fallback — لا يلتقط /api أو /sanctum (مهم مع php artisan serve)
|--------------------------------------------------------------------------
*/
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api(?:/|$)|sanctum(?:/|$)|dashboard(?:/|$)|build(?:/|$)|storage(?:/|$)|up$).*$');
