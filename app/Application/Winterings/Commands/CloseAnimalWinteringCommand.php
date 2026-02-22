<?php

namespace App\Application\Winterings\Commands;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Models\Wintering;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseAnimalWinteringCommand
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver
    ) {
    }

    public function handle(int $animalId): void
    {
        $cycleRows = $this->cycleResolver->resolveCurrentCycleForAnimal($animalId)->values();
        if ($cycleRows->isEmpty()) {
            throw ValidationException::withMessages([
                'rows' => 'Brak cyklu zimowania do zakonczenia.',
            ]);
        }

        if (!$this->cycleResolver->isCycleActive($cycleRows)) {
            throw ValidationException::withMessages([
                'rows' => 'Biezacy cykl zimowania jest juz zakonczony.',
            ]);
        }

        $today = Carbon::today()->toDateString();

        DB::transaction(function () use ($cycleRows, $today): void {
            $currentIndex = $cycleRows->search(function (Wintering $row): bool {
                $hasAnyDate = $row->start_date !== null
                    || $row->planned_start_date !== null
                    || $row->planned_end_date !== null;

                return $hasAnyDate && $row->end_date === null;
            });

            if ($currentIndex === false) {
                throw ValidationException::withMessages([
                    'rows' => 'Nie znaleziono aktywnego etapu zimowania.',
                ]);
            }

            foreach ($cycleRows as $index => $row) {
                if (!$row instanceof Wintering) {
                    continue;
                }

                if ($index < $currentIndex) {
                    continue;
                }

                if ($index === $currentIndex) {
                    if ($row->start_date === null) {
                        $row->start_date = $today;
                    }
                    if ($row->planned_start_date === null) {
                        $row->planned_start_date = $today;
                    }

                    $row->end_date = $today;
                    $row->planned_end_date = $today;
                    $row->save();

                    continue;
                }

                $row->start_date = null;
                $row->end_date = null;
                $row->planned_start_date = null;
                $row->planned_end_date = null;
                $row->save();
            }
        });
    }
}

