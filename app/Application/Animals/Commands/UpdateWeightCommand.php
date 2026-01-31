<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\WeightUpdated;
use App\Models\AnimalWeight;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateWeightCommand
{
    public function handle(array $data): AnimalWeight
    {
        $weight = AnimalWeight::query()
            ->where('id', $data['id'])
            ->where('animal_id', $data['animal_id'])
            ->first();

        if (!$weight) {
            throw new ModelNotFoundException();
        }

        $payload = Arr::only($data, ['value']);
        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($weight, $payload, $occurredAt): AnimalWeight {
            $weight->fill($payload);

            if ($occurredAt) {
                $weight->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $weight->created_at = $timestamp;
                $weight->updated_at = $timestamp;
            }

            $weight->save();

            DB::afterCommit(static function () use ($weight): void {
                event(new WeightUpdated($weight));
            });

            return $weight;
        });
    }
}
