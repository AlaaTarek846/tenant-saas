<?php

namespace App\Support;

use App\Enums\AccountTypeEnum;

class ChartOfAccounts
{
    public const CASH = '1000';

    public const ACCOUNTS_RECEIVABLE = '1100';

    public const DEFERRED_REVENUE = '2100';

    public const SUBSCRIPTION_REVENUE = '4000';

    /**
     * @return list<array{code: string, name: string, type: AccountTypeEnum}>
     */
    public static function defaults(): array
    {
        return [
            ['code' => self::CASH, 'name' => 'Cash', 'type' => AccountTypeEnum::ASSET],
            ['code' => self::ACCOUNTS_RECEIVABLE, 'name' => 'Accounts Receivable', 'type' => AccountTypeEnum::ASSET],
            ['code' => self::DEFERRED_REVENUE, 'name' => 'Deferred Revenue', 'type' => AccountTypeEnum::LIABILITY],
            ['code' => self::SUBSCRIPTION_REVENUE, 'name' => 'Subscription Revenue', 'type' => AccountTypeEnum::REVENUE],
        ];
    }
}
