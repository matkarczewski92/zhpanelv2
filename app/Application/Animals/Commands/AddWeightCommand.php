<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\WeightAdded;
use App\Models\AnimalWeight;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AddWeightCommand
{
    public function handle(array $data): AnimalWeight
    {
        $payload = Arr::only($data, ['animal_id', 'value']);
        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($payload, $occurredAt): AnimalWeight {
            $weight = new AnimalWeight($payload);

            if ($occurredAt) {
                $weight->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $weight->created_at = $timestamp;
                $weight->updated_at = $timestamp;
            }

            $weight->save();

            DB::afterCommit(static function () use ($weight): void {
                event(new WeightAdded($weight));
            });

            return $weight;
        });
    }
}
