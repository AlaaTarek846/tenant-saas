<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Exceptions\ConflictException;
use App\Models\User;
use App\Repositories\Admin\JournalEntryRepository;
use App\Services\Admin\Concerns\HandlesTenantScopedAdmin;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService extends BaseService
{
    use HandlesTenantScopedAdmin;

    public function __construct(
        JournalEntryRepository $repository,
        protected AccountingService $accountingService,
    ) {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor)
            ->with(['details.account'])
            ->withCount('details');

        if ($search = request('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($from = request('from')) {
            $query->whereDate('entry_date', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('entry_date', '<=', $to);
        }

        if ($source = request('source')) {
            if ($source === 'manual') {
                $query->whereNull('reference_type');
            } elseif ($source === 'invoice') {
                $query->where('reference_type', (new Invoice)->getMorphClass());
            } elseif ($source === 'payment') {
                $query->where('reference_type', (new Payment)->getMorphClass());
            }
        }

        $query->latest('entry_date')->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null): JournalEntry
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        $data = $this->applyTenantId($data, $actor);

        return DB::transaction(function () use ($data, $lines) {
            return $this->accountingService->createManualEntry(
                tenantId: (int) $data['tenant_id'],
                description: $data['description'],
                entryDate: Carbon::parse($data['entry_date']),
                lines: $lines,
            )->load('details.account');
        });
    }

    public function show($id, ?User $actor = null): JournalEntry
    {
        $entry = $this->repository->show($id);
        $this->ensureCanAccess($entry, $actor, 'ليس لديك صلاحية الوصول لهذا القيد.');

        return $entry->load(['details.account']);
    }

    public function destroy($id, ?User $actor = null): bool
    {
        $entry = $this->repository->show($id);
        $this->ensureCanAccess($entry, $actor, 'ليس لديك صلاحية حذف هذا القيد.');

        if ($entry->reference_type !== null) {
            throw new ConflictException('لا يمكن حذف القيود المُنشأة تلقائياً من الفواتير أو المدفوعات.');
        }

        return DB::transaction(function () use ($id, $entry) {
            $entry->details()->delete();

            return (bool) $this->repository->destroy($id);
        });
    }
}
