<?php

namespace App\Domain\Winterings;

use App\Models\Wintering;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface WinteringRepositoryInterface
{
    public function getActiveByStage(int $stageId): Collection;

    public function search(WinteringCriteria $criteria, int $perPage = 50): LengthAwarePaginator;
}
