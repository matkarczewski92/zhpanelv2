<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\LitterPregnancyShedRecorded;
use App\Models\Litter;
use App\Models\LitterPregnancyShed;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class RecordLitterPregnancyShedCommand
{
    public function handle(array $data): LitterPregnancyShed
    {
        $litter = Litter::query()
            ->where('id', $data['litter_id'])
            ->where('parent_female', $data['animal_id'])
            ->whereIn('category', [1, 3])
            ->first();

        if (!$litter) {
            throw new ModelNotFoundException();
        }

        return DB::transaction(function () use ($litter, $data): LitterPregnancyShed {
            $shed = LitterPregnancyShed::query()->create([
                'litter_id' => $litter->id,
                'shed_date' => $data['shed_date'],
            ]);

            DB::afterCommit(static function () use ($shed): void {
                event(new LitterPregnancyShedRecorded($shed));
            });

            return $shed;
        });
    }
}
