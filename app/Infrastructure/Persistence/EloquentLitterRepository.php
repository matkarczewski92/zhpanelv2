<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Breeding\LitterRepositoryInterface;
use App\Models\Litter;

class EloquentLitterRepository implements LitterRepositoryInterface
{
    public function findById(int $id): ?Litter
    {
        return Litter::find($id);
    }

    public function getWithPairings(int $id): ?Litter
    {
        return Litter::with(['pairings', 'pairings.summaries'])->find($id);
    }
}
