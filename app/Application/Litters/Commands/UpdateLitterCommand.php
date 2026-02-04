<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterUpdated;
use App\Models\Litter;
use App\Models\LitterAdnotation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateLitterCommand
{
    public function handle(Litter $litter, array $data): Litter
    {
        $payload = Arr::only($data, [
            'category',
            'litter_code',
            'season',
            'parent_male',
            'parent_female',
            'planned_connection_date',
            'connection_date',
            'laying_date',
            'hatching_date',
            'laying_eggs_total',
            'laying_eggs_ok',
            'hatching_eggs',
        ]);

        return DB::transaction(function () use ($litter, $payload, $data): Litter {
            $litter->fill($payload);
            $litter->save();

            if (array_key_exists('adnotation', $data)) {
                $this->syncAdnotation($litter, $data['adnotation']);
            }

            DB::afterCommit(static function () use ($litter): void {
                event(new LitterUpdated($litter));
            });

            return $litter->fresh(['adnotation']);
        });
    }

    private function syncAdnotation(Litter $litter, ?string $adnotation): void
    {
        $value = trim((string) $adnotation);
        $record = LitterAdnotation::query()->where('litter_id', $litter->id)->first();

        if ($value === '') {
            $record?->delete();
            return;
        }

        if (!$record) {
            LitterAdnotation::query()->create([
                'litter_id' => $litter->id,
                'adnotation' => $value,
            ]);
            return;
        }

        $record->adnotation = $value;
        $record->save();
    }
}
