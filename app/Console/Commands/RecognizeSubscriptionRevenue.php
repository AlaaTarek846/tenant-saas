<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Admin\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecognizeSubscriptionRevenue extends Command
{
    protected $signature = 'subscriptions:recognize-revenue {--as-of=} {--tenant=}';

    protected $description = 'Recognize deferred revenue as subscription revenue (end-of-month simulation)';

    public function handle(AccountingService $accountingService): int
    {
        $asOf = $this->option('as-of')
            ? Carbon::parse($this->option('as-of'))
            : now();

        $query = Tenant::query();

        if ($tenantId = $this->option('tenant')) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $totalEntries = 0;

        foreach ($tenants as $tenant) {
            $entries = $accountingService->recognizeRevenue($tenant->id, $asOf);
            $count = count($entries);
            $totalEntries += $count;

            if ($count > 0) {
                $this->info("Tenant #{$tenant->id} ({$tenant->name}): {$count} revenue recognition entry(ies).");
            }
        }

        $this->info("Recognized revenue for {$totalEntries} invoice(s) as of {$asOf->toDateString()}.");

        return self::SUCCESS;
    }
}
