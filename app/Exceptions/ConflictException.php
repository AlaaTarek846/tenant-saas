<?php

namespace App\Exceptions;

use App\Support\DeletionGuard;
use Exception;

class ConflictException extends Exception
{
    /**
     * @param  list<array{relation: string, label: string, count: int}>  $blockers
     */
    public function __construct(
        string $message,
        public readonly array $blockers = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  list<array{relation: string, label: string, count: int}>  $blockers
     */
    public static function becauseRelated(array $blockers, ?string $resourceLabel = null): self
    {
        $parts = array_map(
            fn (array $blocker) => DeletionGuard::formatBlocker($blocker),
            $blockers,
        );

        $prefix = $resourceLabel !== null
            ? "لا يمكن حذف {$resourceLabel} لوجود بيانات مرتبطة"
            : 'لا يمكن الحذف لوجود بيانات مرتبطة';

        return new self($prefix.': '.implode('، ', $parts), $blockers);
    }
}
