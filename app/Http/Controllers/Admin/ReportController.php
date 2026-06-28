<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function incomeStatement(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()));

        $report = $this->reportService->incomeStatement($request->user(), $from, $to);

        return responseJson(200, 'تم إنشاء قائمة الدخل.', $report);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        $report = $this->reportService->balanceSheet($request->user(), $asOf);

        return responseJson(200, 'تم إنشاء الميزانية العمومية.', $report);
    }
}
