<?php

namespace App\Support;

class AdminPermissions
{
    /** @var list<string> */
    public const USER = [
        'create_user',
        'read_user',
        'update_user',
        'delete_user',
    ];

    /** @var list<string> */
    public const ROLE = [
        'create_role',
        'read_role',
        'update_role',
        'delete_role',
    ];

    /** @var list<string> */
    public const TENANT = [
        'create_tenant',
        'read_tenant',
        'update_tenant',
        'delete_tenant',
    ];

    /** @var list<string> */
    public const CUSTOMER = [
        'create_customer',
        'read_customer',
        'update_customer',
        'delete_customer',
    ];

    /** @var list<string> */
    public const SUBSCRIPTION_PLAN = [
        'create_subscription_plan',
        'read_subscription_plan',
        'update_subscription_plan',
        'delete_subscription_plan',
    ];

    /** @var list<string> */
    public const SUBSCRIPTION = [
        'create_subscription',
        'read_subscription',
        'update_subscription',
        'delete_subscription',
    ];

    /** @var list<string> */
    public const INVOICE = [
        'create_invoice',
        'read_invoice',
        'update_invoice',
        'delete_invoice',
    ];

    /** @var list<string> */
    public const PAYMENT = [
        'create_payment',
        'read_payment',
        'update_payment',
        'delete_payment',
    ];

    /** @var list<string> */
    public const ACCOUNT = [
        'create_account',
        'read_account',
        'update_account',
        'delete_account',
    ];

    /** @var list<string> */
    public const SUBSCRIPTION_MODULES = [
        ...self::CUSTOMER,
        ...self::SUBSCRIPTION_PLAN,
        ...self::SUBSCRIPTION,
        ...self::INVOICE,
        ...self::PAYMENT,
        ...self::ACCOUNT,
    ];

    /** @var list<string> */
    public const COMPANY = [
        ...self::USER,
        ...self::ROLE,
        ...self::SUBSCRIPTION_MODULES,
    ];

    public static function userRead(): string
    {
        return 'Super_Admin|Company_Admin|read_user';
    }

    public static function userCreate(): string
    {
        return 'Super_Admin|Company_Admin|create_user';
    }

    public static function userUpdate(): string
    {
        return 'Super_Admin|Company_Admin|update_user';
    }

    public static function userDelete(): string
    {
        return 'Super_Admin|Company_Admin|delete_user';
    }

    public static function roleRead(): string
    {
        return 'Super_Admin|Company_Admin|read_role';
    }

    public static function roleCreate(): string
    {
        return 'Super_Admin|Company_Admin|create_role';
    }

    public static function roleUpdate(): string
    {
        return 'Super_Admin|Company_Admin|update_role';
    }

    public static function roleDelete(): string
    {
        return 'Super_Admin|Company_Admin|delete_role';
    }

    public static function panelAccess(): string
    {
        return 'Super_Admin|Company_Admin|'.implode('|', self::COMPANY);
    }

    public static function tenantRead(): string
    {
        return 'Super_Admin|read_tenant';
    }

    public static function tenantDelete(): string
    {
        return 'Super_Admin|delete_tenant';
    }

    public static function module(string $module, string $action): string
    {
        return 'Super_Admin|Company_Admin|'.$action.'_'.$module;
    }
}
