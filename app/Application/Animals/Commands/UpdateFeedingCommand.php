<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\FeedingUpdated;
use App\Models\AnimalFeeding;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateFeedingCommand
{
    public function handle(array $data): AnimalFeeding
    {
        $feeding = AnimalFeeding::query()
            ->where('id', $data['id'])
            ->where('animal_id', $data['animal_id'])
            ->first();

        if (!$feeding) {
            throw new ModelNotFoundException();
        }

        $payload = Arr::only($data, ['feed_id', 'amount']);
        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($feeding, $payload, $occurredAt): AnimalFeeding {
            $feeding->fill($payload);

            if ($occurredAt) {
                $feeding->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $feeding->created_at = $timestamp;
                $feeding->updated_at = $timestamp;
            }

            $feeding->save();

            DB::afterCommit(static function () use ($feeding): void {
                event(new FeedingUpdated($feeding));
            });

            return $feeding;
        });
    }
}
