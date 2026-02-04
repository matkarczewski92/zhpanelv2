<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterDeleted;
use App\Models\Litter;
use Illuminate\Support\Facades\DB;

class DeleteLitterCommand
{
    public function handle(Litter $litter): void
    {
        DB::transaction(function () use ($litter): void {
            $litter->delete();

            DB::afterCommit(static function () use ($litter): void {
                event(new LitterDeleted($litter->id));
            });
        });
    }
}

