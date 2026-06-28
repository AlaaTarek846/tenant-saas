<?php

namespace App\Enums;

enum AccountTypeEnum: string
{
    case ASSET = 'Asset';
    case LIABILITY = 'Liability';
    case REVENUE = 'Revenue';
    case EXPENSE = 'Expense';
}
