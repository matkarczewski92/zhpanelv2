<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Winterings\WinteringCriteria;
use App\Domain\Winterings\WinteringRepositoryInterface;
use App\Models\Wintering;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentWinteringRepository implements WinteringRepositoryInterface
{
    public function getActiveByStage(int $stageId): Collection
    {
        return Wintering::query()
            ->where('stage_id', $stageId)
            ->where(function ($query) {
                return $query->whereNull('archive')->orWhere('archive', 0);
            })
            ->get();
    }

    public function search(WinteringCriteria $criteria, int $perPage = 50): LengthAwarePaginator
    {
        $query = Wintering::query()->with(['animal', 'stage']);

        $criteria->apply($query);

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }
}
