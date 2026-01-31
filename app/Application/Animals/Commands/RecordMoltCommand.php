<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\MoltRecorded;
use App\Models\AnimalMolt;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RecordMoltCommand
{
    public function handle(array $data): AnimalMolt
    {
        $payload = Arr::only($data, ['animal_id']);
        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($payload, $occurredAt): AnimalMolt {
            $molt = new AnimalMolt($payload);

            if ($occurredAt) {
                $molt->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $molt->created_at = $timestamp;
                $molt->updated_at = $timestamp;
            }

            $molt->save();

            DB::afterCommit(static function () use ($molt): void {
                event(new MoltRecorded($molt));
            });

            return $molt;
        });
    }
}
