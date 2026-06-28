<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Services\Admin\SubscriptionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function __construct(protected SubscriptionPlanService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            SubscriptionPlanResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب خطط الاشتراك.', $data, $paginate);
    }

    public function options(Request $request): JsonResponse
    {
        return responseJson(200, 'تم جلب الخطط.', $this->service->options($request->user()));
    }

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء خطة الاشتراك.', new SubscriptionPlanResource($record));
    }

    public function show(Request $request, int $subscriptionPlan): JsonResponse
    {
        $record = $this->service->show($subscriptionPlan, $request->user());

        return responseJson(200, 'تم جلب خطة الاشتراك.', new SubscriptionPlanResource($record));
    }

    public function update(UpdateSubscriptionPlanRequest $request, int $subscriptionPlan): JsonResponse
    {
        $record = $this->service->update($subscriptionPlan, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث خطة الاشتراك.', new SubscriptionPlanResource($record));
    }

    public function destroy(Request $request, int $subscriptionPlan): JsonResponse
    {
        $this->service->destroy($subscriptionPlan, $request->user());

        return responseJson(200, 'تم حذف خطة الاشتراك.');
    }
}
