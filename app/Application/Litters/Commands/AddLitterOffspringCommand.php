<?php

namespace App\Application\Litters\Commands;

use App\Domain\Events\LitterOffspringAdded;
use App\Models\Animal;
use App\Models\Litter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AddLitterOffspringCommand
{
    public function handle(Litter $litter, int $amount): void
    {
        DB::transaction(function () use ($litter, $amount): void {
            $animalTypeId = $this->resolveAnimalType($litter);
            $birthDate = $litter->hatching_date
                ? CarbonImmutable::parse($litter->hatching_date)
                : CarbonImmutable::now();

            $startIndex = Animal::query()
                ->where('litter_id', $litter->id)
                ->count();

            for ($i = 1; $i <= $amount; $i++) {
                $sequence = $startIndex + $i;

                $animal = Animal::query()->create([
                    'animal_category_id' => 2,
                    'animal_type_id' => $animalTypeId,
                    'name' => $litter->litter_code . ' - Waz nr ' . $sequence,
                    'sex' => 1,
                    'date_of_birth' => $birthDate->format('Y-m-d'),
                    'litter_id' => $litter->id,
                ]);

                $animal->public_profile_tag = $this->buildPublicProfileTag($animal->id);
                $animal->save();
            }

            DB::afterCommit(static function () use ($litter, $amount): void {
                event(new LitterOffspringAdded($litter->id, $amount));
            });
        });
    }

    private function resolveAnimalType(Litter $litter): ?int
    {
        $maleType = Animal::query()->whereKey($litter->parent_male)->value('animal_type_id');
        if ($maleType) {
            return (int) $maleType;
        }

        $femaleType = Animal::query()->whereKey($litter->parent_female)->value('animal_type_id');
        return $femaleType ? (int) $femaleType : null;
    }

    private function buildPublicProfileTag(int $animalId): string
    {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 5);
        return substr((string) $animalId . $suffix, 0, 16);
    }
}

