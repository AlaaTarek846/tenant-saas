<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\Admin\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            CustomerResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب العملاء.', $data, $paginate);
    }

    public function options(Request $request): JsonResponse
    {
        return responseJson(200, 'تم جلب العملاء.', $this->service->options($request->user()));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم إنشاء العميل.', new CustomerResource($record));
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        $record = $this->service->show($customer, $request->user());

        return responseJson(200, 'تم جلب العميل.', new CustomerResource($record));
    }

    public function update(UpdateCustomerRequest $request, int $customer): JsonResponse
    {
        $record = $this->service->update($customer, $request->validated(), $request->user());

        return responseJson(200, 'تم تحديث العميل.', new CustomerResource($record));
    }

    public function destroy(Request $request, int $customer): JsonResponse
    {
        $this->service->destroy($customer, $request->user());

        return responseJson(200, 'تم حذف العميل.');
    }
}
