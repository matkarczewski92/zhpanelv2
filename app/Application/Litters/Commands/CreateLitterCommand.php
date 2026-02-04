<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterCreated;
use App\Models\Litter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateLitterCommand
{
    public function handle(array $data): Litter
    {
        $payload = Arr::only($data, [
            'category',
            'litter_code',
            'season',
            'parent_male',
            'parent_female',
            'planned_connection_date',
        ]);

        return DB::transaction(function () use ($payload): Litter {
            $litter = Litter::query()->create($payload);

            DB::afterCommit(static function () use ($litter): void {
                event(new LitterCreated($litter));
            });

            return $litter;
        });
    }
}

