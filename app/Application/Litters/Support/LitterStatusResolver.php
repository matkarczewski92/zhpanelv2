<?php

namespace App\Application\Litters\Support;

use App\Models\Litter;
use Illuminate\Database\Eloquent\Builder;

class LitterStatusResolver
{
    public const STATUS_WAITING_CONNECTION = 'waiting_connection';
    public const STATUS_WAITING_LAYING = 'waiting_laying';
    public const STATUS_INCUBATION = 'incubation';
    public const STATUS_FEEDING = 'feeding';
    public const STATUS_CLOSED = 'closed';

    public function categoryLabel(int $category): string
    {
        return match ($category) {
            1 => 'Miot',
            2 => 'Planowany',
            3 => 'Szablon',
            4 => 'Zrealizowany',
            default => 'Nieznany',
        };
    }

    public function statusLabel(Litter $litter): string
    {
        if ((int) $litter->category === 4) {
            return 'Zakonczony';
        }

        if (!$litter->connection_date) {
            return 'Oczekiwanie na laczenie';
        }

        if (!$litter->laying_date) {
            return 'Oczekiwanie na zniesienie';
        }

        if (!$litter->hatching_date) {
            return 'W trakcie inkubacji';
        }

        return 'W trakcie odchowu';
    }

    public function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_CLOSED => $query->where('category', 4),
            self::STATUS_WAITING_CONNECTION => $query->where('category', '!=', 4)->whereNull('connection_date'),
            self::STATUS_WAITING_LAYING => $query->where('category', '!=', 4)->whereNotNull('connection_date')->whereNull('laying_date'),
            self::STATUS_INCUBATION => $query->where('category', '!=', 4)->whereNotNull('laying_date')->whereNull('hatching_date'),
            self::STATUS_FEEDING => $query->where('category', '!=', 4)->whereNotNull('hatching_date'),
            default => $query,
        };
    }
}

