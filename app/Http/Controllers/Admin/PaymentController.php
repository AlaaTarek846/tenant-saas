<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\Admin\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            PaymentResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب المدفوعات.', $data, $paginate);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم تسجيل الدفع.', new PaymentResource($record));
    }

    public function show(Request $request, int $payment): JsonResponse
    {
        $record = $this->service->show($payment, $request->user());

        return responseJson(200, 'تم جلب الدفع.', new PaymentResource($record));
    }

    public function update(UpdatePaymentRequest $request, int $payment): JsonResponse
    {
        $record = $this->service->update($payment, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث الدفع.', new PaymentResource($record));
    }

    public function destroy(Request $request, int $payment): JsonResponse
    {
        $this->service->destroy($payment, $request->user());

        return responseJson(200, 'تم حذف الدفع.');
    }
}
