<?php

namespace App\Console\Commands;

use App\Services\Admin\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateSubscriptionInvoices extends Command
{
    protected $signature = 'subscriptions:generate-invoices {--as-of=}';

    protected $description = 'Generate recurring invoices for active subscriptions due for billing';

    public function handle(BillingService $billingService): int
    {
        $asOf = $this->option('as-of')
            ? Carbon::parse($this->option('as-of'))
            : null;

        $invoices = $billingService->generateInvoices(null, $asOf);

        $this->info('Generated '.count($invoices).' invoice(s).');

        foreach ($invoices as $invoice) {
            $this->line("- {$invoice['invoice_number']} (subscription #{$invoice['subscription_id']}) — {$invoice['total']}");
        }

        return self::SUCCESS;
    }
}
