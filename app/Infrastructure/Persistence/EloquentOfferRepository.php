<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Offers\OfferCriteria;
use App\Domain\Offers\OfferRepositoryInterface;
use App\Models\AnimalOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOfferRepository implements OfferRepositoryInterface
{
    public function findOffer(int $id): ?AnimalOffer
    {
        return AnimalOffer::with(['animal', 'reservations'])->find($id);
    }

    public function search(OfferCriteria $criteria, int $perPage = 50): LengthAwarePaginator
    {
        $query = AnimalOffer::query()->with('animal');

        $criteria->apply($query);

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }
}
