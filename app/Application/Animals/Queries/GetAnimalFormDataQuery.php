<?php

namespace App\Application\Animals\Queries;

use App\Domain\Shared\Enums\Sex;
use App\Models\AnimalCategory;
use App\Models\AnimalType;
use App\Models\Feed;
use App\Models\Litter;

class GetAnimalFormDataQuery
{
    public function handle(): array
    {
        return [
            'types' => AnimalType::orderBy('name')->get(),
            'categories' => AnimalCategory::orderBy('name')->get(),
            'feeds' => Feed::orderBy('name')->get(),
            'litters' => Litter::orderByDesc('id')->limit(100)->get(),
            'sexOptions' => Sex::options(),
        ];
    }
}
