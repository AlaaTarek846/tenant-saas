<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->userService->allOrPaginate(
            UserResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب المستخدمين.', $data, $paginate);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء المستخدم.', new UserResource($user));
    }

    public function show(Request $request, int $user): JsonResponse
    {
        $record = $this->userService->show($user, $request->user());

        return responseJson(200, 'تم جلب المستخدم.', new UserResource($record));
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $record = $this->userService->update($user, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث المستخدم.', new UserResource($record));
    }

    public function destroy(DestroyUserRequest $request, int $user): JsonResponse
    {
        $action = $request->validated('action');

        $this->userService->destroy($user, $request->user(), $action);

        $message = match ($action) {
            'suspend' => 'تم إيقاف الشركة وجميع مستخدميها.',
            'force' => 'تم حذف الشركة وجميع بياناتها نهائياً.',
            default => 'تم حذف المستخدم.',
        };

        return responseJson(200, $message);
    }
}
