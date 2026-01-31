<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Animals\AnimalCriteria;
use App\Domain\Animals\AnimalRepositoryInterface;
use App\Models\Animal;
use App\Models\AnimalFeeding;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentAnimalRepository implements AnimalRepositoryInterface
{
    public function findById(int $id): ?Animal
    {
        return Animal::find($id);
    }

    public function getByIds(array $ids): Collection
    {
        return Animal::whereIn('id', $ids)->get();
    }

    public function search(AnimalCriteria $criteria, int $perPage = 50): LengthAwarePaginator
    {
        $query = Animal::query()->with(['animalCategory', 'animalType', 'feed']);

        $criteria->apply($query);

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getProfile(int $id): Animal
    {
        return Animal::query()
            ->with([
                'animalCategory',
                'animalType',
                'feed',
                'litter',
                'photos',
                'offers',
                'feedings' => fn ($query) => $query->with('feed')->latest('created_at')->limit(10),
                'weights' => fn ($query) => $query->latest('created_at')->limit(10),
                'molts' => fn ($query) => $query->latest('created_at')->limit(10),
            ])
            ->findOrFail($id);
    }

    public function getToFeedList(Carbon $date, ?int $typeId = null): Collection
    {
        $lastFeedings = AnimalFeeding::query()
            ->selectRaw('animal_id, MAX(created_at) as last_feeding_at')
            ->groupBy('animal_id');

        $threshold = $date->copy()->subDays(7);

        $query = Animal::query()
            ->leftJoinSub($lastFeedings, 'last_feedings', function ($join): void {
                $join->on('animals.id', '=', 'last_feedings.animal_id');
            })
            ->when($typeId, function ($query, int $typeId) {
                return $query->where('animals.animal_type_id', $typeId);
            })
            ->where(function ($query) use ($threshold) {
                return $query
                    ->whereNull('last_feeding_at')
                    ->orWhere('last_feeding_at', '<=', $threshold);
            })
            ->select('animals.*');

        return $query->get();
    }
}
