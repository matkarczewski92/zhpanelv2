<?php

namespace App\Application\Litters\Support;

use App\Models\Litter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

class IncubationTimelineBuilder
{
    public function __construct(
        private readonly LitterTimelineCalculator $litterTimelineCalculator
    ) {
    }

    public function buildActiveBoardForLitter(Litter $litter): array
    {
        if (!$this->matchesIncubationScope($litter, true)) {
            return ['visible' => false];
        }

        $item = $this->buildIncubationTimelineItem($litter);

        return [
            'visible' => true,
            'items' => [$item],
            'sort_timestamp' => (int) ($item['sort_timestamp'] ?? PHP_INT_MAX),
        ];
    }

    public function buildActiveTimelineItem(Litter $litter): array
    {
        return $this->buildIncubationTimelineItem($litter);
    }

    private function matchesIncubationScope(Litter $litter, bool $activeOnly): bool
    {
        if ($litter->laying_date === null) {
            return false;
        }

        if (!$activeOnly) {
            return true;
        }

        return $litter->hatching_date === null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIncubationTimelineItem(Litter $litter): array
    {
        $start = $litter->laying_date ? CarbonImmutable::parse($litter->laying_date) : null;
        $plannedEnd = $start ? $start->addDays($this->litterTimelineCalculator->getHatchingDuration()) : null;
        $actualEnd = $litter->hatching_date ? CarbonImmutable::parse($litter->hatching_date) : null;
        $end = $plannedEnd;

        if ($actualEnd && (!$end || $actualEnd->gt($end))) {
            $end = $actualEnd;
        } elseif (!$end) {
            $end = $actualEnd;
        }

        $progressPercent = 0.0;
        $plannedPercent = null;
        $actualPercent = null;
        $totalDays = $start && $end ? $start->diffInDays($end) : null;

        if ($start && $end) {
            $progressReference = $actualEnd ?? CarbonImmutable::now();
            $progressPercent = $this->resolveTimelinePercent($start, $progressReference, $end);
            $plannedPercent = $plannedEnd ? $this->resolveTimelinePercent($start, $plannedEnd, $end) : null;
            $actualPercent = $actualEnd ? $this->resolveTimelinePercent($start, $actualEnd, $end) : null;
        }

        $title = trim((string) ($litter->litter_code ?? ''));
        if ($title === '') {
            $title = 'L#' . $litter->id;
        }

        $femaleName = $this->sanitizeName($litter->femaleParent?->name);
        $maleName = $this->sanitizeName($litter->maleParent?->name);

        $subtitleParts = [];
        if ($femaleName !== '') {
            $subtitleParts[] = 'samica: ' . $femaleName;
        }
        if ($maleName !== '') {
            $subtitleParts[] = 'samiec: ' . $maleName;
        }

        return [
            'litter_id' => (int) $litter->id,
            'title' => $title,
            'subtitle' => !empty($subtitleParts) ? implode(' | ', $subtitleParts) : null,
            'egg_stats_label' => 'Jaja do inkubacji: ' . $this->formatEggCount($litter->laying_eggs_ok)
                . ' | Wyklute: ' . $this->formatEggCount($litter->hatching_eggs),
            'start_date_label' => $start?->format('Y-m-d') ?? '-',
            'planned_end_label' => $plannedEnd?->format('Y-m-d') ?? '-',
            'actual_end_label' => $actualEnd?->format('Y-m-d'),
            'progress_percent' => $progressPercent,
            'planned_percent' => $plannedPercent,
            'actual_percent' => $actualPercent,
            'show_range' => $start !== null && $end !== null,
            'duration_badge' => $this->buildIncubationDurationBadge($plannedEnd, $actualEnd, $totalDays),
            'start_tooltip' => $this->buildTimelineTooltip('Start inkubacji', $start, $start),
            'planned_tooltip' => $this->buildTimelineTooltip('Plan konca inkubacji', $plannedEnd, $start),
            'actual_tooltip' => $this->buildTimelineTooltip('Koniec inkubacji', $actualEnd, $start),
            'range_label' => $start && $end ? $start->format('Y-m-d') . ' - ' . $end->format('Y-m-d') : 'Brak pelnego zakresu dat',
            'end_delta_label' => $this->buildEndDeltaLabel($plannedEnd, $actualEnd),
            'show_url' => Route::has('panel.litters.show') ? route('panel.litters.show', $litter->id) : '#',
            'sort_timestamp' => ($plannedEnd ?? $start ?? CarbonImmutable::create(2999, 1, 1))->getTimestamp(),
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

        return implode(' | ', $parts);
    }

    private function buildIncubationDurationBadge(
        ?CarbonImmutable $plannedEnd,
        ?CarbonImmutable $actualEnd,
        ?int $totalDays
    ): ?string {
        if ($actualEnd && $totalDays !== null) {
            return 'Koniec inkubacji po ' . $totalDays . ' dniach';
        }

        if (!$plannedEnd) {
            return null;
        }

        $today = CarbonImmutable::today();
        $days = $today->diffInDays($plannedEnd, false);

        if ($days > 0) {
            return 'Do konca inkubacji pozostalo ' . $days . ' dni';
        }

        if ($days === 0) {
            return 'Planowany koniec inkubacji jest dzisiaj';
        }

        return 'Planowany koniec inkubacji opozniony o ' . abs($days) . ' dni';
    }

    private function buildEndDeltaLabel(?CarbonImmutable $plannedEnd, ?CarbonImmutable $actualEnd): ?string
    {
        if (!$plannedEnd || !$actualEnd) {
            return null;
        }

        $days = $plannedEnd->diffInDays($actualEnd);

        if ($days === 0) {
            return 'Koniec inkubacji nastapil zgodnie z planowana data.';
        }

        if ($actualEnd->lt($plannedEnd)) {
            return 'Koniec inkubacji nastapil ' . $days . ' dni przed planowana data.';
        }

        return 'Koniec inkubacji nastapil ' . $days . ' dni po planowanej dacie.';
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

    private function formatEggCount(?int $value): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format((int) $value, 0, ',', ' ') . ' szt.';
    }

    private function sanitizeName(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }
}
