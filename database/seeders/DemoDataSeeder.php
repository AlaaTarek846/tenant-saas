<?php

namespace Database\Seeders;

use App\Enums\BillingCycleEnum;
use App\Enums\CustomerStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionPlanStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Admin\AccountingService;
use App\Services\Admin\ChartOfAccountsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = $this->resolveCompanyTenant();

        DB::transaction(function () use ($tenant) {
            $this->resetTenantBusinessData($tenant);

            app(ChartOfAccountsService::class)->seedForTenant($tenant);

            $basicPlan = SubscriptionPlan::create([
                'tenant_id' => $tenant->id,
                'name' => 'Basic',
                'description' => 'خطة أساسية للشركات الصغيرة',
                'price' => 50.00,
                'billing_cycle' => BillingCycleEnum::MONTHLY,
                'currency' => 'USD',
                'status' => SubscriptionPlanStatusEnum::ACTIVE,
            ]);
            $basicPlan->features()->createMany([
                ['feature' => 'Users', 'value' => '5'],
                ['feature' => 'Support', 'value' => 'Email'],
            ]);

            $proPlan = SubscriptionPlan::create([
                'tenant_id' => $tenant->id,
                'name' => 'Pro',
                'description' => 'خطة احترافية للشركات المتوسطة',
                'price' => 100.00,
                'billing_cycle' => BillingCycleEnum::MONTHLY,
                'currency' => 'USD',
                'status' => SubscriptionPlanStatusEnum::ACTIVE,
            ]);
            $proPlan->features()->createMany([
                ['feature' => 'Users', 'value' => 'Unlimited'],
                ['feature' => 'Support', 'value' => 'Priority'],
            ]);

            $ahmed = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'أحمد محمد',
                'email' => 'ahmed@demo.com',
                'phone' => '01000000001',
                'address' => 'القاهرة، مصر',
                'status' => CustomerStatusEnum::ACTIVE,
            ]);

            $sara = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'سارة علي',
                'email' => 'sara@demo.com',
                'phone' => '01000000002',
                'address' => 'الإسكندرية، مصر',
                'status' => CustomerStatusEnum::ACTIVE,
            ]);

            $lastMonthStart = now()->subMonth()->startOfMonth();
            $lastMonthEnd = now()->subMonth()->endOfMonth();
            $thisMonthStart = now()->startOfMonth();
            $nextMonthStart = now()->addMonth()->startOfMonth();

            $ahmedSubscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $ahmed->id,
                'subscription_plan_id' => $proPlan->id,
                'start_date' => $lastMonthStart,
                'end_date' => null,
                'next_billing_date' => $thisMonthStart,
                'status' => SubscriptionStatusEnum::ACTIVE,
            ]);

            $saraSubscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $sara->id,
                'subscription_plan_id' => $basicPlan->id,
                'start_date' => $thisMonthStart,
                'end_date' => null,
                'next_billing_date' => $nextMonthStart,
                'status' => SubscriptionStatusEnum::ACTIVE,
            ]);

            /** @var AccountingService $accounting */
            $accounting = app(AccountingService::class);

            $paidInvoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $ahmed->id,
                'subscription_id' => $ahmedSubscription->id,
                'invoice_number' => 'INV-'.$tenant->id.'-00001',
                'issue_date' => $lastMonthStart,
                'due_date' => $lastMonthStart->copy()->addDays(7),
                'subtotal' => 100.00,
                'discount' => 0,
                'tax' => 0,
                'total' => 100.00,
                'status' => InvoiceStatusEnum::PAID,
            ]);
            $paidInvoice->items()->create([
                'subscription_plan_id' => $proPlan->id,
                'description' => 'Pro — monthly',
                'quantity' => 1,
                'unit_price' => 100.00,
                'total' => 100.00,
            ]);

            $accounting->recordInvoiceIssued($paidInvoice->fresh());

            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $paidInvoice->id,
                'payment_method' => 'Bank Transfer',
                'reference' => 'PAY-001',
                'amount' => 100.00,
                'paid_at' => $lastMonthStart->copy()->addDays(3),
                'status' => PaymentStatusEnum::PAID,
            ]);
            $accounting->recordPaymentReceived($payment->fresh());

            $accounting->recognizeRevenue($tenant->id, $lastMonthEnd);

            $pendingInvoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $sara->id,
                'subscription_id' => $saraSubscription->id,
                'invoice_number' => 'INV-'.$tenant->id.'-00002',
                'issue_date' => $thisMonthStart,
                'due_date' => $thisMonthStart->copy()->addDays(7),
                'subtotal' => 50.00,
                'discount' => 0,
                'tax' => 0,
                'total' => 50.00,
                'status' => InvoiceStatusEnum::PENDING,
            ]);
            $pendingInvoice->items()->create([
                'subscription_plan_id' => $basicPlan->id,
                'description' => 'Basic — monthly',
                'quantity' => 1,
                'unit_price' => 50.00,
                'total' => 50.00,
            ]);

            $accounting->recordInvoiceIssued($pendingInvoice->fresh());
        });

        $this->command?->info('Demo data seeded for Company 1 (companyadmin@gmail.com).');
    }

    protected function resolveCompanyTenant(): Tenant
    {
        $tenant = User::query()
            ->where('email', 'companyadmin@gmail.com')
            ->value('tenant_id');

        if (! $tenant) {
            $tenant = Tenant::query()->where('email', 'company1@gmail.com')->value('id');
        }

        $model = Tenant::query()->find($tenant);

        if (! $model) {
            throw new \RuntimeException('Company 1 tenant not found. Run UserSeeder first.');
        }

        return $model;
    }

    protected function resetTenantBusinessData(Tenant $tenant): void
    {
        $tenantId = $tenant->id;

        DB::table('journal_entry_details')
            ->whereIn('journal_entry_id', function ($query) use ($tenantId) {
                $query->select('id')->from('journal_entries')->where('tenant_id', $tenantId);
            })
            ->delete();

        DB::table('journal_entries')->where('tenant_id', $tenantId)->delete();
        DB::table('payments')->where('tenant_id', $tenantId)->delete();
        DB::table('invoice_items')
            ->whereIn('invoice_id', function ($query) use ($tenantId) {
                $query->select('id')->from('invoices')->where('tenant_id', $tenantId);
            })
            ->delete();
        DB::table('invoices')->where('tenant_id', $tenantId)->delete();
        DB::table('subscriptions')->where('tenant_id', $tenantId)->delete();
        DB::table('subscription_plan_features')
            ->whereIn('subscription_plan_id', function ($query) use ($tenantId) {
                $query->select('id')->from('subscription_plans')->where('tenant_id', $tenantId);
            })
            ->delete();
        DB::table('subscription_plans')->where('tenant_id', $tenantId)->delete();
        DB::table('customers')->where('tenant_id', $tenantId)->delete();
        DB::table('accounts')->where('tenant_id', $tenantId)->delete();
    }
}
