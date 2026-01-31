<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\FeedingDeleted;
use App\Models\AnimalFeeding;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteFeedingCommand
{
    public function handle(int $animalId, int $feedingId): void
    {
        $feeding = AnimalFeeding::query()
            ->where('id', $feedingId)
            ->where('animal_id', $animalId)
            ->first();

        if (!$feeding) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($feeding): void {
            $feeding->delete();

            DB::afterCommit(static function () use ($feeding): void {
                event(new FeedingDeleted($feeding));
            });
        });
    }
}
