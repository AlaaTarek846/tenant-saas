<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->dashboardService->getData($user);

        return responseJson(200, 'تم جلب بيانات لوحة الإدارة.', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'stats' => $data['stats'],
            'recent_users' => $data['recent_users'],
            'recent_tenants' => $data['recent_tenants'],
            'recent_customers' => $data['recent_customers'],
            'recent_invoices' => $data['recent_invoices'],
            'recent_payments' => $data['recent_payments'],
        ]);
    }
}
