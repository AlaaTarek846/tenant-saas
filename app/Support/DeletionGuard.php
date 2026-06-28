<?php

namespace App\Support;

use App\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Model;

class DeletionGuard
{
    /**
     * @param  array<string, string>  $relations  relation method => Arabic label
     */
    public static function ensureDeletable(Model $model, array $relations, ?string $resourceLabel = null): void
    {
        $blockers = self::findBlockers($model, $relations);

        if ($blockers !== []) {
            throw ConflictException::becauseRelated($blockers, $resourceLabel);
        }
    }

    /**
     * @param  array<string, string>  $relations
     * @return list<array{relation: string, label: string, count: int}>
     */
    public static function findBlockers(Model $model, array $relations): array
    {
        $blockers = [];

        foreach ($relations as $relation => $label) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            $count = (int) $model->$relation()->count();

            if ($count > 0) {
                $blockers[] = [
                    'relation' => $relation,
                    'label' => $label,
                    'count' => $count,
                ];
            }
        }

        return $blockers;
    }

    /**
     * @param  array{relation: string, label: string, count: int}  $blocker
     */
    public static function formatBlocker(array $blocker): string
    {
        return sprintf('%s (%d)', $blocker['label'], $blocker['count']);
    }
}
