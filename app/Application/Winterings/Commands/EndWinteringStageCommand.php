<?php

namespace App\Application\Winterings\Commands;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Application\Winterings\Support\WinteringTimelineCalculator;
use App\Models\Wintering;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndWinteringStageCommand
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver,
        private readonly WinteringTimelineCalculator $timelineCalculator
    ) {
    }

    public function handle(int $animalId, int $winteringId): void
    {
        $cycleRows = $this->cycleResolver->resolveCurrentCycleForAnimal($animalId);
        $ordered = $cycleRows->values();
        $index = $ordered->search(fn (Wintering $row): bool => (int) $row->id === $winteringId);

        if ($index === false) {
            throw ValidationException::withMessages([
                'rows' => 'Wybrany etap zimowania nie nalezy do aktywnego cyklu.',
            ]);
        }

        /** @var Wintering $target */
        $target = $ordered->get((int) $index);
        /** @var Wintering|null $next */
        $next = $ordered->get((int) $index + 1);
        $today = Carbon::today()->toDateString();

        DB::transaction(function () use ($ordered, $index, $target, $next, $today): void {
            $target->end_date = $today;
            $target->planned_end_date = $today;
            $target->save();

            if ($next instanceof Wintering) {
                $next->start_date = $today;
                $next->planned_start_date = $today;
                $next->save();
            }

            if (!$next instanceof Wintering) {
                return;
            }

            $rows = $ordered->map(fn (Wintering $row): array => [
                'wintering_id' => (int) $row->id,
                'default_duration' => (int) ($row->stage?->duration ?? 0),
                'custom_duration' => $row->custom_duration,
                'planned_start_date' => $row->planned_start_date?->toDateString(),
                'planned_end_date' => $row->planned_end_date?->toDateString(),
                'start_date' => $row->start_date?->toDateString(),
                'end_date' => $row->end_date?->toDateString(),
            ])->all();

            $nextIndex = (int) $index + 1;
            $rows[$nextIndex]['planned_start_date'] = $today;
            $rows = $this->timelineCalculator->recalculateForward($rows, $nextIndex);
            $this->persistPlannedDates($ordered, $rows, $nextIndex);
        });
    }

    /**
     * @param Collection<int, Wintering> $ordered
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistPlannedDates(Collection $ordered, array $rows, int $fromIndex): void
    {
        for ($i = $fromIndex; $i < count($rows); $i++) {
            /** @var Wintering|null $model */
            $model = $ordered->get($i);
            if (!$model instanceof Wintering) {
                continue;
            }

            $model->planned_start_date = $rows[$i]['planned_start_date'] ?? null;
            $model->planned_end_date = $rows[$i]['planned_end_date'] ?? null;
            $model->save();
        }
    }
}

