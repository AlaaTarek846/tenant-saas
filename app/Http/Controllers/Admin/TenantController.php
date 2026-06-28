<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantActionRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Admin\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        protected TenantService $tenantService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->tenantService->allOrPaginate(
            TenantResource::class,
        );

        return responseJson(200, 'تم جلب الشركات.', $data, $paginate);
    }

    public function show(int $tenant): JsonResponse
    {
        $record = $this->tenantService->show($tenant);

        return responseJson(200, 'تم جلب الشركة.', new TenantResource($record));
    }

    public function destroy(TenantActionRequest $request, int $tenant): JsonResponse
    {
        $action = $request->validated('action');

        if ($action === 'suspend') {
            $record = $this->tenantService->suspend($tenant);

            return responseJson(200, 'تم إيقاف الشركة وجميع مستخدميها.', new TenantResource($record));
        }

        $this->tenantService->forceDestroy($tenant);

        return responseJson(200, 'تم حذف الشركة وجميع بياناتها نهائياً.');
    }

    public function options(): JsonResponse
    {
        $tenants = Tenant::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return responseJson(200, 'تم جلب المستأجرين.', $tenants);
    }
}
