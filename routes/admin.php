<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('profile', [ProfileController::class, 'updateUser'])->name('profile.update');
    Route::post('profile/company', [ProfileController::class, 'updateCompany'])
        ->middleware('role:Company_Admin')
        ->name('profile.company.update');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::userRead());
    Route::get('users/{user}', [UserController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::userRead());
    Route::post('users', [UserController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::userCreate());
    Route::put('users/{user}', [UserController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::userUpdate());
    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::userUpdate());
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::userDelete());

    Route::get('roles/options', [RoleController::class, 'options'])
        ->middleware('role_or_permission:'.AdminPermissions::userCreate())
        ->name('roles.options');

    Route::get('permissions', [RoleController::class, 'permissions'])
        ->middleware('role_or_permission:Super_Admin|Company_Admin|create_role|update_role')
        ->name('permissions.index');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::roleRead());
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::roleRead());
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::roleCreate());
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::roleUpdate());
    Route::patch('roles/{role}', [RoleController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::roleUpdate());
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::roleDelete());

    Route::middleware('role:Super_Admin')->group(function () {
        Route::get('tenants/options', [TenantController::class, 'options'])->name('tenants.options');
        Route::get('tenants', [TenantController::class, 'index'])
            ->middleware('role_or_permission:'.AdminPermissions::tenantRead());
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])
            ->middleware('role_or_permission:'.AdminPermissions::tenantRead());
        Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
            ->middleware('role_or_permission:'.AdminPermissions::tenantDelete());
    });

    Route::get('customers/options', [CustomerController::class, 'options'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'read'));
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'read'));
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'create'));
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'read'));
    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'update'));
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'update'));
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('customer', 'delete'));

    Route::get('subscription-plans/options', [SubscriptionPlanController::class, 'options'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'read'));
    Route::get('subscription-plans', [SubscriptionPlanController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'read'));
    Route::post('subscription-plans', [SubscriptionPlanController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'create'));
    Route::get('subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'read'));
    Route::put('subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'update'));
    Route::patch('subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'update'));
    Route::delete('subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription_plan', 'delete'));

    Route::get('subscriptions/options', [SubscriptionController::class, 'options'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'read'));
    Route::get('subscriptions', [SubscriptionController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'read'));
    Route::post('subscriptions', [SubscriptionController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'create'));
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'read'));
    Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'update'));
    Route::patch('subscriptions/{subscription}', [SubscriptionController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'update'));
    Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('subscription', 'delete'));

    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'read'));
    Route::post('invoices', [InvoiceController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'create'));
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'read'));
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'update'));
    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'update'));
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'delete'));

    Route::get('payments', [PaymentController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'read'));
    Route::post('payments', [PaymentController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'create'));
    Route::get('payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'read'));
    Route::put('payments/{payment}', [PaymentController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'update'));
    Route::patch('payments/{payment}', [PaymentController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'update'));
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('payment', 'delete'));

    Route::get('accounts', [AccountController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
    Route::post('accounts', [AccountController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'create'));
    Route::get('accounts/{account}', [AccountController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
    Route::put('accounts/{account}', [AccountController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'update'));
    Route::patch('accounts/{account}', [AccountController::class, 'update'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'update'));
    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'delete'));

    Route::get('journal-entries', [JournalEntryController::class, 'index'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
    Route::post('journal-entries', [JournalEntryController::class, 'store'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'create'));
    Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
    Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'delete'));

    Route::post('billing/generate-invoices', [BillingController::class, 'generateInvoices'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'create'));
    Route::post('billing/recognize-revenue', [BillingController::class, 'recognizeRevenue'])
        ->middleware('role_or_permission:'.AdminPermissions::module('invoice', 'update'));

    Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])
        ->middleware('role_or_permission:'.AdminPermissions::module('account', 'read'));
});
