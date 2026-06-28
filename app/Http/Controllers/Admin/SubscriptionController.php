<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\Admin\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            SubscriptionResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب الاشتراكات.', $data, $paginate);
    }

    public function options(Request $request): JsonResponse
    {
        return responseJson(200, 'تم جلب الاشتراكات.', $this->service->options($request->user()));
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء الاشتراك.', new SubscriptionResource($record));
    }

    public function show(Request $request, int $subscription): JsonResponse
    {
        $record = $this->service->show($subscription, $request->user());

        return responseJson(200, 'تم جلب الاشتراك.', new SubscriptionResource($record));
    }

    public function update(UpdateSubscriptionRequest $request, int $subscription): JsonResponse
    {
        $record = $this->service->update($subscription, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث الاشتراك.', new SubscriptionResource($record));
    }

    public function destroy(Request $request, int $subscription): JsonResponse
    {
        $this->service->destroy($subscription, $request->user());

        return responseJson(200, 'تم حذف الاشتراك.');
    }
}
