<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterOffspringBulkUpdated;
use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Litter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateLitterOffspringBatchCommand
{
    /**
     * @param array<int, array{id:int,name:string,sex:int,weight:float|null}> $rows
     */
    public function handle(Litter $litter, array $rows): int
    {
        return DB::transaction(function () use ($litter, $rows): int {
            $ids = collect($rows)->pluck('id')->map(fn ($id) => (int) $id)->all();

            $animals = Animal::query()
                ->where('litter_id', $litter->id)
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            $updatedCount = 0;
            $weightDate = CarbonImmutable::now()->format('Y-m-d');

            foreach ($rows as $row) {
                $animal = $animals->get((int) $row['id']);
                if (!$animal) {
                    continue;
                }

                $name = trim((string) $row['name']);
                $sex = (int) $row['sex'];

                $dirty = false;
                if ($animal->name !== $name) {
                    $animal->name = $name;
                    $dirty = true;
                }

                if ((int) $animal->sex !== $sex) {
                    $animal->sex = $sex;
                    $dirty = true;
                }

                if ($dirty) {
                    $animal->save();
                    $updatedCount++;
                }

                $this->appendWeightIfChanged((int) $animal->id, $row['weight'] ?? null, $weightDate);
            }

            DB::afterCommit(static function () use ($litter, $updatedCount): void {
                event(new LitterOffspringBulkUpdated($litter->id, $updatedCount));
            });

            return $updatedCount;
        });
    }

    private function appendWeightIfChanged(int $animalId, ?float $weight, string $date): void
    {
        if ($weight === null) {
            return;
        }

        $lastWeight = AnimalWeight::query()
            ->where('animal_id', $animalId)
            ->orderByDesc('created_at')
            ->value('value');

        if ($lastWeight !== null && (float) $lastWeight === (float) $weight) {
            return;
        }

        $entry = new AnimalWeight([
            'animal_id' => $animalId,
            'value' => $weight,
        ]);
        $entry->created_at = $date;
        $entry->updated_at = $date;
        $entry->save();
    }
}
