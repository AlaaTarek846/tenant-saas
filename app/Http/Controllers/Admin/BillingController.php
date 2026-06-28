<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\JournalEntryResource;
use App\Services\Admin\AccountingService;
use App\Services\Admin\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected AccountingService $accountingService,
    ) {}

    public function generateInvoices(Request $request): JsonResponse
    {
        $asOf = $request->date('as_of');

        $invoices = $this->billingService->generateInvoices(
            $request->user(),
            $asOf ? Carbon::parse($asOf) : null,
        );

        return responseJson(200, 'تم إنشاء الفواتير الدورية.', [
            'count' => count($invoices),
            'invoices' => $invoices,
        ]);
    }

    public function recognizeRevenue(Request $request): JsonResponse
    {
        $user = $request->user();
        $asOf = $request->date('as_of') ? Carbon::parse($request->date('as_of')) : now();

        $tenantId = $user->tenant_id ?? $request->integer('tenant_id');

        if (! $tenantId) {
            return responseJson(422, 'يجب تحديد tenant_id لمدير المنصة.');
        }

        $entries = $this->accountingService->recognizeRevenue($tenantId, $asOf);

        $loaded = collect($entries)->each(fn ($entry) => $entry->load('details.account'));

        return responseJson(200, 'تم اعتراف الإيراد للفترة.', [
            'count' => $loaded->count(),
            'as_of' => $asOf->toDateString(),
            'journal_entry_ids' => $loaded->pluck('id')->values(),
            'entries' => JournalEntryResource::collection($loaded),
        ]);
    }
}
