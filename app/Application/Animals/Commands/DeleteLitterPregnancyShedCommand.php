<?php

namespace App\Application\Animals\Commands;

use App\Models\LitterPregnancyShed;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteLitterPregnancyShedCommand
{
    public function handle(int $animalId, int $shedId): void
    {
        $shed = LitterPregnancyShed::query()
            ->where('id', $shedId)
            ->whereHas('litter', function ($query) use ($animalId): void {
                $query
                    ->where('parent_female', $animalId)
                    ->whereIn('category', [1, 3]);
            })
            ->first();

        if (!$shed) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($shed): void {
            $shed->delete();
        });
    }
}
