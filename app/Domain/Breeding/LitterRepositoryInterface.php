<?php

namespace App\Domain\Breeding;

use App\Models\Litter;

interface LitterRepositoryInterface
{
    public function findById(int $id): ?Litter;

    public function getWithPairings(int $id): ?Litter;
}
