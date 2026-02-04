<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterUpdated;
use App\Models\Litter;
use App\Models\LitterAdnotation;
use Illuminate\Support\Facades\DB;

class UpdateLitterAdnotationCommand
{
    public function handle(Litter $litter, ?string $adnotation): void
    {
        DB::transaction(function () use ($litter, $adnotation): void {
            $value = trim((string) $adnotation);
            $record = LitterAdnotation::query()->where('litter_id', $litter->id)->first();

            if ($value === '') {
                $record?->delete();
            } elseif (!$record) {
                LitterAdnotation::query()->create([
                    'litter_id' => $litter->id,
                    'adnotation' => $value,
                ]);
            } else {
                $record->adnotation = $value;
                $record->save();
            }

            DB::afterCommit(static function () use ($litter): void {
                event(new LitterUpdated($litter));
            });
        });
    }
}

