<?php

namespace App\Services\Admin;

use App\Enums\AccountTypeEnum;
use App\Models\JournalEntryDetail;
use App\Models\User;
use App\Support\ChartOfAccounts;
use App\Support\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function incomeStatement(?User $actor, Carbon $from, Carbon $to): array
    {
        $tenantId = $this->resolveTenantId($actor);
        $rows = $this->accountActivity($tenantId, $from, $to, [AccountTypeEnum::REVENUE]);

        $lines = [];
        $totalRevenue = 0.0;

        foreach ($rows as $row) {
            $amount = round((float) $row->credits - (float) $row->debits, 2);
            $lines[] = [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'amount' => $amount,
            ];
            $totalRevenue += $amount;
        }

        $subscriptionRevenue = collect($lines)->firstWhere('code', ChartOfAccounts::SUBSCRIPTION_REVENUE);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'lines' => $lines,
            'subscription_revenue' => [
                'code' => ChartOfAccounts::SUBSCRIPTION_REVENUE,
                'name' => $subscriptionRevenue['name'] ?? 'Subscription Revenue',
                'amount' => round((float) ($subscriptionRevenue['amount'] ?? 0), 2),
            ],
            'total_revenue' => round($totalRevenue, 2),
            'net_income' => round($totalRevenue, 2),
        ];
    }

    public function balanceSheet(?User $actor, Carbon $asOf): array
    {
        $tenantId = $this->resolveTenantId($actor);

        $assets = $this->mapBalances(
            $this->accountBalances($tenantId, $asOf, [AccountTypeEnum::ASSET]),
            fn (float $balance) => $balance,
        );

        $liabilities = $this->mapBalances(
            $this->accountBalances($tenantId, $asOf, [AccountTypeEnum::LIABILITY]),
            fn (float $balance) => $balance * -1,
        );

        $totalAssets = round(collect($assets)->sum('balance'), 2);
        $totalLiabilities = round(collect($liabilities)->sum('balance'), 2);

        $highlight = $this->keyAccountBalances($tenantId, $asOf);

        return [
            'as_of' => $asOf->toDateString(),
            'key_accounts' => $highlight,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
            ],
            'highlight' => [
                'cash' => $highlight[0]['balance'] ?? 0,
                'accounts_receivable' => $highlight[1]['balance'] ?? 0,
                'deferred_revenue' => $highlight[2]['balance'] ?? 0,
            ],
        ];
    }

    /**
     * @return list<array{code: string, name: string, type: string, balance: float}>
     */
    protected function keyAccountBalances(int $tenantId, Carbon $asOf): array
    {
        $definitions = [
            ['code' => ChartOfAccounts::CASH, 'name' => 'Cash', 'type' => 'Asset'],
            ['code' => ChartOfAccounts::ACCOUNTS_RECEIVABLE, 'name' => 'Accounts Receivable', 'type' => 'Asset'],
            ['code' => ChartOfAccounts::DEFERRED_REVENUE, 'name' => 'Deferred Revenue', 'type' => 'Liability'],
        ];

        $rows = JournalEntryDetail::query()
            ->select([
                'accounts.code',
                DB::raw('COALESCE(SUM(journal_entry_details.debit), 0) as debits'),
                DB::raw('COALESCE(SUM(journal_entry_details.credit), 0) as credits'),
            ])
            ->join('accounts', 'accounts.id', '=', 'journal_entry_details.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.tenant_id', $tenantId)
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->whereIn('accounts.code', array_column($definitions, 'code'))
            ->groupBy('accounts.code')
            ->get()
            ->keyBy('code');

        return collect($definitions)->map(function (array $definition) use ($rows) {
            $row = $rows->get($definition['code']);
            $raw = $row ? (float) $row->debits - (float) $row->credits : 0.0;
            $balance = $definition['type'] === 'Liability'
                ? round($raw * -1, 2)
                : round($raw, 2);

            return [
                'code' => $definition['code'],
                'name' => $definition['name'],
                'type' => $definition['type'],
                'balance' => $balance,
            ];
        })->values()->all();
    }

    protected function resolveTenantId(?User $actor): int
    {
        if (TenantScope::isScoped($actor)) {
            return TenantScope::id($actor);
        }

        $tenantId = request()->integer('tenant_id');

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => ['يجب تحديد tenant_id لمدير المنصة.'],
            ]);
        }

        return $tenantId;
    }

    /**
     * @param  list<AccountTypeEnum>  $types
     */
    protected function accountActivity(int $tenantId, Carbon $from, Carbon $to, array $types)
    {
        return JournalEntryDetail::query()
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_details.debit), 0) as debits'),
                DB::raw('COALESCE(SUM(journal_entry_details.credit), 0) as credits'),
            ])
            ->join('accounts', 'accounts.id', '=', 'journal_entry_details.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.tenant_id', $tenantId)
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('accounts.type', array_map(fn (AccountTypeEnum $type) => $type->value, $types))
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();
    }

    /**
     * @param  list<AccountTypeEnum>  $types
     */
    protected function accountBalances(int $tenantId, Carbon $asOf, array $types)
    {
        return JournalEntryDetail::query()
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(journal_entry_details.debit), 0) as debits'),
                DB::raw('COALESCE(SUM(journal_entry_details.credit), 0) as credits'),
            ])
            ->join('accounts', 'accounts.id', '=', 'journal_entry_details.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.tenant_id', $tenantId)
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->whereIn('accounts.type', array_map(fn (AccountTypeEnum $type) => $type->value, $types))
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return list<array{code: string, name: string, type: string, balance: float}>
     */
    protected function mapBalances($rows, callable $transform): array
    {
        return $rows->map(function ($row) use ($transform) {
            $balance = round((float) $row->debits - (float) $row->credits, 2);

            return [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'balance' => $transform($balance),
            ];
        })->values()->all();
    }
}
