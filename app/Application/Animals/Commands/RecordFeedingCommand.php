<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\FeedingRecorded;
use App\Models\AnimalFeeding;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RecordFeedingCommand
{
    public function handle(array $data): AnimalFeeding
    {
        $payload = Arr::only($data, ['animal_id', 'feed_id', 'amount']);
        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($payload, $occurredAt): AnimalFeeding {
            $feeding = new AnimalFeeding($payload);

            if ($occurredAt) {
                $feeding->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $feeding->created_at = $timestamp;
                $feeding->updated_at = $timestamp;
            }

            $feeding->save();

            DB::afterCommit(static function () use ($feeding): void {
                event(new FeedingRecorded($feeding));
            });

            return $feeding;
        });
    }
}
