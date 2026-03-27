<?php

namespace App\Application\Animals\Support;

use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use App\Models\Litter;
use App\Models\LitterPregnancyShed;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class PregnancyTimelineBuilder
{
    public function __construct(
        private readonly LitterTimelineCalculator $litterTimelineCalculator
    ) {
    }

    public function buildActiveBoardForAnimal(Animal $animal): array
    {
        if ((int) $animal->sex !== Sex::Female->value) {
            return ['visible' => false];
        }

        $litters = $this->resolveLitters($animal, true);
        if ($litters->isEmpty()) {
            return ['visible' => false];
        }

        $items = $litters
            ->sortBy(fn (Litter $litter) => $this->resolveBoardSortTimestamp($litter))
            ->values()
            ->map(fn (Litter $litter): array => $this->buildPregnancyTimelineItem($animal, $litter, 'active'))
            ->all();

        return [
            'visible' => true,
            'selected_season_key' => 'active',
            'selected_season_label' => 'Aktywne ciaze',
            'season_options' => [],
            'items' => $items,
            'store_url' => route('panel.animals.pregnancy-sheds.store', $animal->id),
            'sort_timestamp' => $this->resolveTimelineSortValue($items),
        ];
    }

    /**
     * @return Collection<int, Litter>
     */
    private function resolveLitters(Animal $animal, bool $activeOnly): Collection
    {
        if ($animal->relationLoaded('littersAsFemale')) {
            /** @var Collection<int, Litter> $loaded */
            $loaded = $animal->littersAsFemale;

            return $loaded
                ->filter(fn (Litter $litter): bool => $this->matchesPregnancyScope($litter, $activeOnly))
                ->values();
        }

        $query = $animal->littersAsFemale()
            ->with([
                'maleParent:id,name',
                'pregnancySheds:id,litter_id,shed_date',
            ])
            ->whereIn('category', [1, 3]);

        if ($activeOnly) {
            $query
                ->where(function ($builder): void {
                    $builder
                        ->whereNotNull('connection_date')
                        ->orWhereNotNull('planned_connection_date');
                })
                ->whereNull('laying_date');
        }

        return $query->get();
    }

    private function matchesPregnancyScope(Litter $litter, bool $activeOnly): bool
    {
        if (!in_array((int) $litter->category, [1, 3], true)) {
            return false;
        }

        if (!$activeOnly) {
            return true;
        }

        return ($litter->connection_date !== null || $litter->planned_connection_date !== null)
            && $litter->laying_date === null;
    }

    private function buildPregnancyTimelineItem(Animal $animal, Litter $litter, string $selectedSeasonKey): array
    {
        $start = $this->resolvePregnancyStartDate($litter);
        $plannedLaying = $this->resolvePregnancyPlannedLayingDate($litter);
        $actualLaying = $litter->laying_date ? CarbonImmutable::parse($litter->laying_date) : null;
        $end = $plannedLaying;

        if ($actualLaying && (!$end || $actualLaying->gt($end))) {
            $end = $actualLaying;
        } elseif (!$end) {
            $end = $actualLaying;
        }

        $now = CarbonImmutable::now();
        $progressPercent = 0.0;
        $plannedPercent = null;
        $actualPercent = null;
        $sheds = [];
        $totalDays = $start && $end ? $start->diffInDays($end) : null;

        if ($start && $end) {
            $progressReference = $actualLaying ?? $now;
            $progressPercent = $this->resolveTimelinePercent($start, $progressReference, $end);
            $plannedPercent = $plannedLaying ? $this->resolveTimelinePercent($start, $plannedLaying, $end) : null;
            $actualPercent = $actualLaying ? $this->resolveTimelinePercent($start, $actualLaying, $end) : null;

            $sheds = $litter->pregnancySheds
                ->sortBy('shed_date')
                ->map(function (LitterPregnancyShed $shed) use ($start, $end): array {
                    $shedDate = $shed->shed_date ? CarbonImmutable::parse($shed->shed_date) : null;

                    return [
                        'id' => $shed->id,
                        'date_label' => $shedDate?->format('Y-m-d') ?? '-',
                        'position_percent' => $shedDate ? $this->resolveTimelinePercent($start, $shedDate, $end) : 0,
                        'tooltip' => $this->buildTimelineTooltip('Wylinka', $shedDate, $start),
                        'delete_url' => route('panel.animals.pregnancy-sheds.destroy', [$animal->id, $shed->id]),
                    ];
                })
                ->values()
                ->all();
        }

        $title = $litter->litter_code ?: ('L#' . $litter->id);
        $maleName = trim(strip_tags((string) optional($litter->maleParent)->name));

        return [
            'litter_id' => $litter->id,
            'title' => $title,
            'subtitle' => $maleName !== '' ? 'samiec: ' . $maleName : null,
            'start_date_label' => $start?->format('Y-m-d') ?? '-',
            'planned_laying_label' => $plannedLaying?->format('Y-m-d') ?? '-',
            'actual_laying_label' => $actualLaying?->format('Y-m-d'),
            'status_label' => $actualLaying ? 'Zniesienie zapisane' : 'W oczekiwaniu na zniesienie',
            'is_completed' => $actualLaying !== null,
            'progress_percent' => $progressPercent,
            'planned_percent' => $plannedPercent,
            'actual_percent' => $actualPercent,
            'sheds' => $sheds,
            'show_range' => $start !== null && $end !== null,
            'duration_badge' => $this->buildPregnancyDurationBadge($plannedLaying, $actualLaying, $totalDays),
            'start_tooltip' => $this->buildTimelineTooltip('Laczenie', $start, $start),
            'planned_tooltip' => $this->buildTimelineTooltip('Plan zniosu', $plannedLaying, $start),
            'actual_tooltip' => $this->buildTimelineTooltip('Znios', $actualLaying, $start),
            'range_label' => $start && $end ? $start->format('Y-m-d') . ' - ' . $end->format('Y-m-d') : 'Brak pelnego zakresu dat',
            'actual_extends_timeline' => $actualLaying && $plannedLaying ? $actualLaying->gt($plannedLaying) : false,
            'laying_delta_label' => $this->buildLayingDeltaLabel($plannedLaying, $actualLaying),
            'store_url' => route('panel.animals.pregnancy-sheds.store', $animal->id),
            'selected_season_key' => $selectedSeasonKey,
            'show_url' => Route::has('panel.litters.show') ? route('panel.litters.show', $litter->id) : '#',
            'sort_timestamp' => $this->resolveBoardSortTimestamp($litter),
        ];
    }

    private function buildTimelineTooltip(string $label, ?CarbonImmutable $date, ?CarbonImmutable $start): string
    {
        if (!$date) {
            return $label;
        }

        $parts = [$label, $date->format('Y-m-d')];
        if ($start) {
            $parts[] = 'dzien ' . $start->diffInDays($date);
        }

        return implode(' • ', $parts);
    }

    private function buildLayingDeltaLabel(?CarbonImmutable $plannedLaying, ?CarbonImmutable $actualLaying): ?string
    {
        if (!$plannedLaying || !$actualLaying) {
            return null;
        }

        $days = $plannedLaying->diffInDays($actualLaying);

        if ($days === 0) {
            return 'Znios nastapil zgodnie z planowana data.';
        }

        if ($actualLaying->lt($plannedLaying)) {
            return 'Znios nastapil ' . $days . ' dni przed planowana data.';
        }

        return 'Znios nastapil ' . $days . ' dni po planowanej dacie.';
    }

    private function buildPregnancyDurationBadge(
        ?CarbonImmutable $plannedLaying,
        ?CarbonImmutable $actualLaying,
        ?int $totalDays
    ): ?string {
        if ($actualLaying && $totalDays !== null) {
            return 'Zniesienie po ' . $totalDays . ' dniach';
        }

        if (!$plannedLaying) {
            return null;
        }

        $today = CarbonImmutable::today();
        $days = $today->diffInDays($plannedLaying, false);

        if ($days > 0) {
            return 'Do planowanego zniosu pozostalo ' . $days . ' dni';
        }

        if ($days === 0) {
            return 'Planowany znios jest dzisiaj';
        }

        return 'Planowany znios opozniony o ' . abs($days) . ' dni';
    }

    private function resolvePregnancyStartDate(Litter $litter): ?CarbonImmutable
    {
        $referenceDate = $litter->connection_date ?: $litter->planned_connection_date;

        return $referenceDate ? CarbonImmutable::parse($referenceDate) : null;
    }

    private function resolvePregnancyPlannedLayingDate(Litter $litter): ?CarbonImmutable
    {
        $start = $this->resolvePregnancyStartDate($litter);
        if (!$start) {
            return null;
        }

        return $start->addDays($this->litterTimelineCalculator->getLayingDuration());
    }

    private function resolveTimelinePercent(CarbonImmutable $start, CarbonImmutable $point, CarbonImmutable $end): float
    {
        if ($point->lte($start)) {
            return 0.0;
        }

        if ($point->gte($end)) {
            return 100.0;
        }

        $totalDays = max($start->diffInDays($end), 1);
        $elapsedDays = $start->diffInDays($point);

        return round(($elapsedDays / $totalDays) * 100, 2);
    }

    private function resolveBoardSortTimestamp(Litter $litter): int
    {
        $planned = $this->resolvePregnancyPlannedLayingDate($litter);
        $start = $this->resolvePregnancyStartDate($litter);

        return ($planned ?? $start ?? CarbonImmutable::create(2999, 1, 1))->getTimestamp();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function resolveTimelineSortValue(array $items): int
    {
        $first = collect($items)
            ->sortBy('sort_timestamp')
            ->first();

        return (int) ($first['sort_timestamp'] ?? CarbonImmutable::create(2999, 1, 1)->getTimestamp());
    }
}
