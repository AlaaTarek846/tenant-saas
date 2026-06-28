<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\Admin\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            InvoiceResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب الفواتير.', $data, $paginate);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء الفاتورة.', new InvoiceResource($record));
    }

    public function show(Request $request, int $invoice): JsonResponse
    {
        $record = $this->service->show($invoice, $request->user());

        return responseJson(200, 'تم جلب الفاتورة.', new InvoiceResource($record));
    }

    public function update(UpdateInvoiceRequest $request, int $invoice): JsonResponse
    {
        $record = $this->service->update($invoice, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث الفاتورة.', new InvoiceResource($record));
    }

    public function destroy(Request $request, int $invoice): JsonResponse
    {
        $this->service->destroy($invoice, $request->user());

        return responseJson(200, 'تم حذف الفاتورة.');
    }
}
