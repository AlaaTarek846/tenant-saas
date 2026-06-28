<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Services\Admin\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(protected AccountService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            AccountResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب الحسابات.', $data, $paginate);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء الحساب.', new AccountResource($record));
    }

    public function show(Request $request, int $account): JsonResponse
    {
        $record = $this->service->show($account, $request->user());

        return responseJson(200, 'تم جلب الحساب.', new AccountResource($record));
    }

    public function update(UpdateAccountRequest $request, int $account): JsonResponse
    {
        $record = $this->service->update($account, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث الحساب.', new AccountResource($record));
    }

    public function destroy(Request $request, int $account): JsonResponse
    {
        $this->service->destroy($account, $request->user());

        return responseJson(200, 'تم حذف الحساب.');
    }
}
