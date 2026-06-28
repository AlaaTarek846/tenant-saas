<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->roleService->allOrPaginate(
            RoleResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب الأدوار.', $data, $paginate);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء الدور.', new RoleResource($role));
    }

    public function show(Request $request, int $role): JsonResponse
    {
        $record = $this->roleService->show($role, $request->user());

        return responseJson(200, 'تم جلب الدور.', new RoleResource($record));
    }

    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        $record = $this->roleService->update($role, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث الدور.', new RoleResource($record));
    }

    public function destroy(Request $request, int $role): JsonResponse
    {
        $this->roleService->destroy($role, $request->user());

        return responseJson(200, 'تم حذف الدور.');
    }

    public function permissions(Request $request): JsonResponse
    {
        $permissions = PermissionResource::collection(
            $this->roleService->listPermissions($request->user())
        );

        return responseJson(200, 'تم جلب الصلاحيات.', $permissions);
    }

    public function options(Request $request): JsonResponse
    {
        $roles = RoleResource::collection($this->roleService->listOptions($request->user()));

        return responseJson(200, 'تم جلب خيارات الأدوار.', $roles);
    }
}
