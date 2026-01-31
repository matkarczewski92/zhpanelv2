<?php

namespace App\Domain\Offers;

use App\Models\AnimalOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OfferRepositoryInterface
{
    public function findOffer(int $id): ?AnimalOffer;

    public function search(OfferCriteria $criteria, int $perPage = 50): LengthAwarePaginator;
}
