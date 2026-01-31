<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\MoltDeleted;
use App\Models\AnimalMolt;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeleteMoltCommand
{
    public function handle(int $animalId, int $moltId): void
    {
        $molt = AnimalMolt::query()
            ->where('id', $moltId)
            ->where('animal_id', $animalId)
            ->first();

        if (!$molt) {
            throw new ModelNotFoundException();
        }

        DB::transaction(function () use ($molt): void {
            $molt->delete();

            DB::afterCommit(static function () use ($molt): void {
                event(new MoltDeleted($molt));
            });
        });
    }
}
