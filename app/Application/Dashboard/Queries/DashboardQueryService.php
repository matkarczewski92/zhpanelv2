<?php

namespace App\Application\Dashboard\Queries;

use App\Application\Dashboard\ViewModels\DashboardViewModel;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\AnimalOffer;
use App\Models\Finance;
use App\Models\FinanceCategory;
use App\Models\Litter;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DashboardQueryService
{
    public function __construct(
        private readonly LitterTimelineCalculator $timelineCalculator,
        private readonly AnimalWinteringCycleResolver $winteringCycleResolver
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters = []): DashboardViewModel
    {
        $financeYears = $this->resolveFinanceYears();
        $requestedYear = isset($filters['year']) ? (int) $filters['year'] : null;
        $selectedYear = ($requestedYear !== null && in_array($requestedYear, $financeYears, true))
            ? $requestedYear
            : ($financeYears[0] ?? (int) now()->year);

        return new DashboardViewModel(
            management: $this->buildManagementTiles(),
            litterStatuses: $this->buildLitterStatuses(),
            financeYears: $financeYears,
            financeSelectedYear: $selectedYear,
            financeSummary: $this->buildFinanceSummary($selectedYear),
            feedingTables: $this->buildFeedingTables()
        );
    }

    /**
     * @return array<string, int|float|string>
     */
    private function buildManagementTiles(): array
    {
        $eggsInIncubation = (int) Litter::query()
            ->where('category', 1)
            ->whereNotNull('laying_date')
            ->whereNull('hatching_date')
            ->sum('laying_eggs_ok');

        return [
            'eggs_in_incubation' => $eggsInIncubation,
            'eggs_in_incubators_total' => $eggsInIncubation,
            'for_sale_count' => (int) Animal::query()->where('animal_category_id', 2)->count(),
            'planned_income' => (float) AnimalOffer::query()->whereNull('sold_date')->sum('price'),
            'litter_count' => (int) Litter::query()->where('category', 1)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLitterStatuses(): array
    {
        $layingLitters = Litter::query()
            ->where('category', 1)
            ->whereNotNull('connection_date')
            ->whereNull('laying_date')
            ->orderBy('connection_date')
            ->get(['id', 'litter_code', 'connection_date', 'laying_date', 'hatching_date']);

        $hatchingLitters = Litter::query()
            ->where('category', 1)
            ->whereNotNull('connection_date')
            ->whereNotNull('laying_date')
            ->whereNull('hatching_date')
            ->orderBy('connection_date')
            ->get(['id', 'litter_code', 'connection_date', 'laying_date', 'hatching_date']);

        return [
            [
                'key' => 'laying',
                'name' => 'Oczekiwanie na zniesienie',
                'badge_class' => 'text-bg-warning',
                'text_class' => 'text-warning',
                'count' => $layingLitters->count(),
                'items' => $layingLitters->map(function (Litter $litter): array {
                    $date = $this->timelineCalculator->estimatedLayingDate($litter);

                    return [
                        'id' => $litter->id,
                        'code' => $litter->litter_code,
                        'date' => $date?->format('Y-m-d'),
                    ];
                })->all(),
            ],
            [
                'key' => 'hatching',
                'name' => 'W trakcie inkubacji',
                'badge_class' => 'text-bg-danger',
                'text_class' => 'text-danger',
                'count' => $hatchingLitters->count(),
                'items' => $hatchingLitters->map(function (Litter $litter): array {
                    $date = $this->timelineCalculator->estimatedHatchingDate($litter);

                    return [
                        'id' => $litter->id,
                        'code' => $litter->litter_code,
                        'date' => $date?->format('Y-m-d'),
                    ];
                })->all(),
            ],
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveFinanceYears(): array
    {
        $years = Finance::query()
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(fn ($year): int => (int) $year)
            ->values()
            ->all();

        if ($years === []) {
            $years[] = (int) now()->year;
        }

        return $years;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFinanceSummary(int $year): array
    {
        $yearIncome = (float) Finance::query()
            ->whereYear('created_at', $year)
            ->where('type', 'i')
            ->sum('amount');
        $yearCosts = (float) Finance::query()
            ->whereYear('created_at', $year)
            ->where('type', 'c')
            ->sum('amount');

        $yearByCategory = Finance::query()
            ->selectRaw('finances_category_id, type, SUM(amount) as total')
            ->whereYear('created_at', $year)
            ->groupBy('finances_category_id', 'type')
            ->get();

        $overallByCategory = Finance::query()
            ->selectRaw('finances_category_id, type, SUM(amount) as total')
            ->groupBy('finances_category_id', 'type')
            ->get();

        $categories = FinanceCategory::query()->pluck('name', 'id');
        $categorySummary = [];

        $seedCategory = function (int $categoryId) use (&$categorySummary, $categories): void {
            $categorySummary[$categoryId] ??= [
                'id' => $categoryId,
                'name' => $categories[$categoryId] ?? 'Nieznana kategoria',
                'income' => 0.0,
                'cost' => 0.0,
                'profit' => 0.0,
                'overall_income' => 0.0,
                'overall_cost' => 0.0,
            ];
        };

        foreach ($yearByCategory as $row) {
            $categoryId = (int) $row->finances_category_id;
            $seedCategory($categoryId);

            if ($row->type === 'i') {
                $categorySummary[$categoryId]['income'] = (float) $row->total;
            }
            if ($row->type === 'c') {
                $categorySummary[$categoryId]['cost'] = (float) $row->total;
            }
        }

        foreach ($overallByCategory as $row) {
            $categoryId = (int) $row->finances_category_id;
            $seedCategory($categoryId);

            if ($row->type === 'i') {
                $categorySummary[$categoryId]['overall_income'] = (float) $row->total;
            }
            if ($row->type === 'c') {
                $categorySummary[$categoryId]['overall_cost'] = (float) $row->total;
            }
        }

        $rows = collect($categorySummary)
            ->map(function (array $row): array {
                $row['profit'] = (float) $row['income'] - (float) $row['cost'];
                $row['income_label'] = $this->formatCurrency((float) $row['income']);
                $row['cost_label'] = $this->formatCurrency((float) $row['cost']);
                $row['profit_label'] = $this->formatCurrency((float) $row['profit']);
                $row['overall_income_label'] = $this->formatCurrency((float) $row['overall_income']);
                $row['overall_cost_label'] = $this->formatCurrency((float) $row['overall_cost']);

                return $row;
            })
            ->filter(function (array $row): bool {
                return (float) $row['income'] !== 0.0
                    || (float) $row['cost'] !== 0.0
                    || (float) $row['overall_income'] !== 0.0
                    || (float) $row['overall_cost'] !== 0.0;
            })
            ->sortBy('id')
            ->values()
            ->all();

        $overallIncome = (float) Finance::query()->where('type', 'i')->sum('amount');
        $overallCosts = (float) Finance::query()->where('type', 'c')->sum('amount');

        return [
            'year' => $year,
            'year_totals' => [
                'income' => $yearIncome,
                'costs' => $yearCosts,
                'profit' => $yearIncome - $yearCosts,
                'income_label' => $this->formatCurrency($yearIncome),
                'costs_label' => $this->formatCurrency($yearCosts),
                'profit_label' => $this->formatCurrency($yearIncome - $yearCosts),
            ],
            'category_totals' => $rows,
            'overall_totals' => [
                'income' => $overallIncome,
                'costs' => $overallCosts,
                'profit' => $overallIncome - $overallCosts,
                'income_label' => $this->formatCurrency($overallIncome),
                'costs_label' => $this->formatCurrency($overallCosts),
                'profit_label' => $this->formatCurrency($overallIncome - $overallCosts),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFeedingTables(): array
    {
        $animals = Animal::query()
            ->with(['feed:id,name,feeding_interval'])
            ->whereIn('animal_category_id', [1, 2])
            ->orderBy('id')
            ->get(['id', 'name', 'animal_category_id', 'feed_id', 'feed_interval']);
        $winteringActiveIds = $this->winteringCycleResolver->resolveActiveAnimalIds(
            $animals->pluck('id')->map(fn ($id): int => (int) $id)->all()
        );
        $winteringActiveMap = array_fill_keys($winteringActiveIds, true);

        $lastFeedingByAnimal = AnimalFeeding::query()
            ->selectRaw('animal_id, MAX(created_at) as last_feeding_at')
            ->whereIn('animal_id', $animals->pluck('id')->all())
            ->groupBy('animal_id')
            ->pluck('last_feeding_at', 'animal_id');

        $tables = [
            'breeding' => [
                'title' => 'Do nakarmienia - W hodowli',
                'rows' => [],
            ],
            'litters' => [
                'title' => 'Do nakarmienia - Mioty',
                'rows' => [],
            ],
        ];

        foreach ($animals as $animal) {
            $isWintering = isset($winteringActiveMap[(int) $animal->id]);
            if ((int) $animal->animal_category_id === 1 && $isWintering) {
                continue;
            }

            $lastFeedingAt = $lastFeedingByAnimal->get($animal->id);
            $interval = $animal->feed_interval ?? $animal->feed?->feeding_interval ?? 0;

            $metrics = $this->calculateFeedingMetrics(
                $lastFeedingAt ? Carbon::parse((string) $lastFeedingAt) : null,
                (int) $interval
            );

            if ($metrics['days_to_feed'] > 1) {
                continue;
            }

            $row = [
                'id' => $animal->id,
                'name' => trim(strip_tags((string) $animal->name)),
                'feed_name' => $animal->feed?->name ?: 'Brak karmy',
                'feed_date' => $metrics['next_feed_date'],
                'days_to_feed' => $metrics['days_to_feed'],
                'days_to_feed_label' => $metrics['days_to_feed_label'],
                'profile_url' => route('panel.animals.show', $animal->id),
            ];

            if ((int) $animal->animal_category_id === 1) {
                $tables['breeding']['rows'][] = $row;
            }

            if ((int) $animal->animal_category_id === 2) {
                $tables['litters']['rows'][] = $row;
            }
        }

        $tables['breeding']['summary'] = $this->buildFeedingSummary($tables['breeding']['rows'], false);
        $tables['breeding']['summary_past'] = $this->buildFeedingSummary($tables['breeding']['rows'], true);
        $tables['litters']['summary'] = $this->buildFeedingSummary($tables['litters']['rows'], false);
        $tables['litters']['summary_past'] = $this->buildFeedingSummary($tables['litters']['rows'], true);

        return $tables;
    }

    /**
     * @return array<string, int|string>
     */
    private function calculateFeedingMetrics(?CarbonInterface $lastFeedingAt, int $feedInterval): array
    {
        if ($lastFeedingAt === null) {
            return [
                'next_feed_date' => '',
                'days_to_feed' => 0,
                'days_to_feed_label' => '0',
            ];
        }

        $today = Carbon::today();
        $nextFeedDate = $lastFeedingAt->copy()->addDays(max(0, $feedInterval));
        $daysToFeed = (int) $today->diffInDays($nextFeedDate->copy()->startOfDay(), false);

        return [
            'next_feed_date' => $nextFeedDate->format('Y-m-d'),
            'days_to_feed' => $daysToFeed,
            'days_to_feed_label' => $daysToFeed > 0 ? '+' . $daysToFeed : (string) $daysToFeed,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function buildFeedingSummary(array $rows, bool $onlyPast): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if ($onlyPast && (int) ($row['days_to_feed'] ?? 0) > 0) {
                continue;
            }

            $feedName = (string) ($row['feed_name'] ?? 'Brak karmy');
            $grouped[$feedName] = ($grouped[$feedName] ?? 0) + 1;
        }

        return $grouped;
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' zł';
    }
}
