<?php

namespace App\Domain\Winterings;

use Illuminate\Database\Eloquent\Builder;

class WinteringCriteria
{
    public ?int $animalId = null;
    public ?int $stageId = null;
    public ?int $season = null;
    public ?bool $archived = null;
    public ?string $plannedFrom = null;
    public ?string $plannedTo = null;
    public ?string $startFrom = null;
    public ?string $startTo = null;

    public function apply(Builder $query): Builder
    {
        $query->when($this->animalId, function (Builder $query, int $animalId): Builder {
            return $query->where('animal_id', $animalId);
        });

        $query->when($this->stageId, function (Builder $query, int $stageId): Builder {
            return $query->where('stage_id', $stageId);
        });

        $query->when($this->season, function (Builder $query, int $season): Builder {
            return $query->where('season', $season);
        });

        if ($this->archived === true) {
            $query->where('archive', 1);
        }

        if ($this->archived === false) {
            $query->where(function (Builder $query): Builder {
                return $query->whereNull('archive')->orWhere('archive', 0);
            });
        }

        $query->when($this->plannedFrom, function (Builder $query, string $plannedFrom): Builder {
            return $query->whereDate('planned_start_date', '>=', $plannedFrom);
        });

        $query->when($this->plannedTo, function (Builder $query, string $plannedTo): Builder {
            return $query->whereDate('planned_end_date', '<=', $plannedTo);
        });

        $query->when($this->startFrom, function (Builder $query, string $startFrom): Builder {
            return $query->whereDate('start_date', '>=', $startFrom);
        });

        $query->when($this->startTo, function (Builder $query, string $startTo): Builder {
            return $query->whereDate('start_date', '<=', $startTo);
        });

        return $query;
    }
}
