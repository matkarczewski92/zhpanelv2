<?php

namespace App\Application\Winterings\Commands;

use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Application\Winterings\Support\WinteringTimelineCalculator;
use App\Models\Wintering;
use App\Models\WinteringStage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveAnimalWinteringPlanCommand
{
    public function __construct(
        private readonly AnimalWinteringCycleResolver $cycleResolver,
        private readonly WinteringTimelineCalculator $timelineCalculator
    ) {
    }

    /**
     * @param array{
     *     animal_id:int,
     *     scheme?:string|null,
     *     rows:array<int, array{
     *         wintering_id?:int|null,
     *         stage_id:int,
     *         planned_start_date?:string|null,
     *         planned_end_date?:string|null,
     *         start_date?:string|null,
     *         end_date?:string|null,
     *         custom_duration?:int|null
     *     }>
     * } $data
     */
    public function handle(array $data): void
    {
        $animalId = (int) ($data['animal_id'] ?? 0);
        if ($animalId <= 0) {
            throw ValidationException::withMessages([
                'rows' => 'Brak poprawnego identyfikatora zwierzecia.',
            ]);
        }

        $existingCycle = $this->cycleResolver->resolveCurrentCycleForAnimal($animalId);
        $hasCycle = $existingCycle->isNotEmpty();

        $rowsPayload = collect($data['rows'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values();

        if ($rowsPayload->isEmpty()) {
            throw ValidationException::withMessages([
                'rows' => 'Brak etapow do zapisania.',
            ]);
        }

        $rows = $hasCycle
            ? $this->buildRowsFromExistingCycle($existingCycle, $rowsPayload)
            : $this->buildRowsForNewCycle((string) ($data['scheme'] ?? ''), $rowsPayload);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => 'Brak poprawnych etapow do zapisania.',
            ]);
        }

        $anchorIndex = $hasCycle
            ? $this->resolveAnchorIndexForExistingCycle($rows, $existingCycle)
            : $this->resolveAnchorIndexForNewCycle($rows);

        $rows = $this->timelineCalculator->recalculateForward($rows, $anchorIndex);
        $rows = $this->applyPreviousStageEndDateRule($rows);

        DB::transaction(function () use ($animalId, $hasCycle, $existingCycle, $rows): void {
            if ($hasCycle) {
                $this->updateExistingCycle($rows, $existingCycle);
            } else {
                $this->createNewCycle($animalId, $rows);
            }
        });
    }

    /**
     * @param Collection<int, Wintering> $existingCycle
     * @param Collection<int, array<string, mixed>> $rowsPayload
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsFromExistingCycle(Collection $existingCycle, Collection $rowsPayload): array
    {
        $payloadByWinteringId = $rowsPayload
            ->filter(fn (array $row): bool => (int) ($row['wintering_id'] ?? 0) > 0)
            ->keyBy(fn (array $row): int => (int) $row['wintering_id']);
        $payloadByStageId = $rowsPayload
            ->filter(fn (array $row): bool => (int) ($row['stage_id'] ?? 0) > 0)
            ->keyBy(fn (array $row): int => (int) $row['stage_id']);

        return $existingCycle
            ->values()
            ->map(function (Wintering $row) use ($payloadByWinteringId, $payloadByStageId): array {
                $payload = $payloadByWinteringId->get((int) $row->id)
                    ?? $payloadByStageId->get((int) $row->stage_id)
                    ?? [];

                return [
                    'wintering_id' => (int) $row->id,
                    'stage_id' => (int) $row->stage_id,
                    'default_duration' => (int) ($row->stage?->duration ?? 0),
                    'custom_duration' => $payload['custom_duration'] ?? $row->custom_duration,
                    'planned_start_date' => $payload['planned_start_date'] ?? $row->planned_start_date?->toDateString(),
                    'planned_end_date' => $payload['planned_end_date'] ?? $row->planned_end_date?->toDateString(),
                    'start_date' => $payload['start_date'] ?? $row->start_date?->toDateString(),
                    'end_date' => $payload['end_date'] ?? $row->end_date?->toDateString(),
                    'end_date_original' => $row->end_date?->toDateString(),
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $rowsPayload
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForNewCycle(string $scheme, Collection $rowsPayload): array
    {
        $scheme = trim($scheme);
        if ($scheme === '') {
            throw ValidationException::withMessages([
                'scheme' => 'Wybierz schemat zimowania.',
            ]);
        }

        $stages = WinteringStage::query()
            ->where('scheme', $scheme)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        if ($stages->isEmpty()) {
            throw ValidationException::withMessages([
                'scheme' => 'Wybrany schemat nie posiada etapow.',
            ]);
        }

        $payloadByStageId = $rowsPayload
            ->filter(fn (array $row): bool => (int) ($row['stage_id'] ?? 0) > 0)
            ->keyBy(fn (array $row): int => (int) $row['stage_id']);

        return $stages
            ->map(function (WinteringStage $stage) use ($payloadByStageId): array {
                $payload = $payloadByStageId->get((int) $stage->id, []);

                return [
                    'wintering_id' => null,
                    'stage_id' => (int) $stage->id,
                    'default_duration' => (int) $stage->duration,
                    'custom_duration' => $payload['custom_duration'] ?? null,
                    'planned_start_date' => $payload['planned_start_date'] ?? null,
                    'planned_end_date' => $payload['planned_end_date'] ?? null,
                    'start_date' => $payload['start_date'] ?? null,
                    'end_date' => $payload['end_date'] ?? null,
                    'end_date_original' => null,
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param Collection<int, Wintering> $existingCycle
     */
    private function resolveAnchorIndexForExistingCycle(array $rows, Collection $existingCycle): int
    {
        $existingById = $existingCycle->keyBy('id');

        foreach ($rows as $index => $row) {
            $winteringId = (int) ($row['wintering_id'] ?? 0);
            $existing = $existingById->get($winteringId);
            if (!$existing instanceof Wintering) {
                continue;
            }

            $newStart = trim((string) ($row['planned_start_date'] ?? ''));
            $newEnd = trim((string) ($row['planned_end_date'] ?? ''));
            $newRealStart = trim((string) ($row['start_date'] ?? ''));
            $newRealEnd = trim((string) ($row['end_date'] ?? ''));
            $newCustom = $row['custom_duration'];

            $oldStart = $existing->planned_start_date?->toDateString() ?? '';
            $oldEnd = $existing->planned_end_date?->toDateString() ?? '';
            $oldRealStart = $existing->start_date?->toDateString() ?? '';
            $oldRealEnd = $existing->end_date?->toDateString() ?? '';
            $oldCustom = $existing->custom_duration;

            if (
                $newStart !== $oldStart
                || $newEnd !== $oldEnd
                || $newRealStart !== $oldRealStart
                || $newRealEnd !== $oldRealEnd
                || (string) $newCustom !== (string) $oldCustom
            ) {
                return (int) $index;
            }
        }

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveAnchorIndexForNewCycle(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $start = trim((string) ($row['planned_start_date'] ?? ''));
            $end = trim((string) ($row['planned_end_date'] ?? ''));
            $realStart = trim((string) ($row['start_date'] ?? ''));
            $realEnd = trim((string) ($row['end_date'] ?? ''));

            if ($start !== '' || $end !== '' || $realStart !== '' || $realEnd !== '') {
                return (int) $index;
            }
        }

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param Collection<int, Wintering> $existingCycle
     */
    private function updateExistingCycle(array $rows, Collection $existingCycle): void
    {
        $modelsById = $existingCycle->keyBy('id');

        foreach ($rows as $row) {
            $winteringId = (int) ($row['wintering_id'] ?? 0);
            $model = $modelsById->get($winteringId);
            if (!$model instanceof Wintering) {
                continue;
            }

            $model->planned_start_date = $this->normalizeDate($row['planned_start_date'] ?? null);
            $model->planned_end_date = $this->normalizeDate($row['planned_end_date'] ?? null);
            $model->start_date = $this->normalizeDate($row['start_date'] ?? null);
            $model->end_date = $this->normalizeDate($row['end_date'] ?? null);
            $model->custom_duration = is_numeric($row['custom_duration'] ?? null)
                ? max(0, (int) $row['custom_duration'])
                : null;
            $model->save();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function createNewCycle(int $animalId, array $rows): void
    {
        $season = $this->resolveSeason($rows);

        foreach ($rows as $row) {
            Wintering::query()->create([
                'animal_id' => $animalId,
                'season' => $season,
                'planned_start_date' => $this->normalizeDate($row['planned_start_date'] ?? null),
                'planned_end_date' => $this->normalizeDate($row['planned_end_date'] ?? null),
                'start_date' => $this->normalizeDate($row['start_date'] ?? null),
                'end_date' => $this->normalizeDate($row['end_date'] ?? null),
                'annotations' => null,
                'stage_id' => (int) $row['stage_id'],
                'custom_duration' => is_numeric($row['custom_duration'] ?? null)
                    ? max(0, (int) $row['custom_duration'])
                    : null,
                'archive' => null,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveSeason(array $rows): int
    {
        $start = trim((string) ($rows[0]['planned_start_date'] ?? ''));
        $date = $start !== '' ? Carbon::parse($start) : Carbon::today();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        return $month >= 9 ? $year : $year - 1;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    /**
     * When stage n receives start_date, stage n-1 gets end_date if it is still empty.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyPreviousStageEndDateRule(array $rows): array
    {
        for ($index = 1; $index < count($rows); $index++) {
            $startDate = trim((string) ($rows[$index]['start_date'] ?? ''));
            if ($startDate === '') {
                continue;
            }

            $previousEndDate = trim((string) ($rows[$index - 1]['end_date'] ?? ''));
            if ($previousEndDate !== '') {
                continue;
            }

            $previousOriginalEndDate = trim((string) ($rows[$index - 1]['end_date_original'] ?? ''));
            $previousWasExplicitlyCleared = $previousOriginalEndDate !== '' && $previousEndDate === '';
            if ($previousWasExplicitlyCleared) {
                continue;
            }

            $rows[$index - 1]['end_date'] = $startDate;
        }

        return $rows;
    }
}
