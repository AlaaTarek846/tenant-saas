<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Services\Admin\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(protected JournalEntryService $service) {}

    public function index(Request $request): JsonResponse
    {
        ['data' => $data, 'paginate' => $paginate] = $this->service->allOrPaginate(
            JournalEntryResource::class,
            null,
            $request->user(),
        );

        return responseJson(200, 'تم جلب القيود المحاسبية.', $data, $paginate);
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $record = $this->service->store($request->validated(), $request->user());

        return responseJson(201, 'تم تسجيل القيد المحاسبي.', new JournalEntryResource($record));
    }

    public function show(Request $request, int $journalEntry): JsonResponse
    {
        $record = $this->service->show($journalEntry, $request->user());

        return responseJson(200, 'تم جلب القيد المحاسبي.', new JournalEntryResource($record));
    }

    public function destroy(Request $request, int $journalEntry): JsonResponse
    {
        $this->service->destroy($journalEntry, $request->user());

        return responseJson(200, 'تم حذف القيد المحاسبي.');
    }
}
