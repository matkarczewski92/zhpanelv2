<?php

namespace App\Domain\Animals;

use App\Models\Animal;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AnimalRepositoryInterface
{
    public function findById(int $id): ?Animal;

    public function getByIds(array $ids): Collection;

    public function search(AnimalCriteria $criteria, int $perPage = 50): LengthAwarePaginator;

    public function getProfile(int $id): Animal;

    public function getToFeedList(Carbon $date, ?int $typeId = null): Collection;
}
