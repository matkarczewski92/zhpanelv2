<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\MoltUpdated;
use App\Models\AnimalMolt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UpdateMoltCommand
{
    public function handle(array $data): AnimalMolt
    {
        $molt = AnimalMolt::query()
            ->where('id', $data['id'])
            ->where('animal_id', $data['animal_id'])
            ->first();

        if (!$molt) {
            throw new ModelNotFoundException();
        }

        $occurredAt = $data['occurred_at'] ?? null;

        return DB::transaction(function () use ($molt, $occurredAt): AnimalMolt {
            if ($occurredAt) {
                $molt->timestamps = false;
                $timestamp = Carbon::parse($occurredAt);
                $molt->created_at = $timestamp;
                $molt->updated_at = $timestamp;
            }

            $molt->save();

            DB::afterCommit(static function () use ($molt): void {
                event(new MoltUpdated($molt));
            });

            return $molt;
        });
    }
}
