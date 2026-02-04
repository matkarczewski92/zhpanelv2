<?php

namespace App\Application\Finances\Queries;

use App\Application\Finances\ViewModels\FinancesIndexViewModel;
use App\Models\Animal;
use App\Models\Feed;
use App\Models\Finance;
use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Builder;

class GetFinancesIndexQuery
{
    public function handle(array $filters): FinancesIndexViewModel
    {
        $baseQuery = Finance::query()
            ->with([
                'category:id,name',
                'feed:id,name',
                'animal:id,name',
            ])
            ->orderByDesc('created_at');

        $filteredQuery = $this->applyFilters($baseQuery, $filters);

        $transactions = $filteredQuery
            ->paginate(15)
            ->withQueryString()
            ->through(function (Finance $finance): array {
                $isIncome = $finance->type === 'i';

                return [
                    'id' => $finance->id,
                    'type' => $finance->type,
                    'type_label' => $isIncome ? 'Dochod' : 'Koszt',
                    'type_class' => $isIncome ? 'text-success' : 'text-danger',
                    'category_id' => $finance->finances_category_id,
                    'category_name' => $finance->category?->name ?? '-',
                    'title' => $finance->title,
                    'amount' => (float) ($finance->amount ?? 0),
                    'amount_label' => $this->formatCurrency((float) ($finance->amount ?? 0)),
                    'created_at' => $finance->created_at?->format('Y-m-d') ?? '-',
                    'feed_id' => $finance->feed_id,
                    'feed_name' => $finance->feed?->name,
                    'animal_id' => $finance->animal_id,
                    'animal_name' => $finance->animal?->name,
                    'animal_profile_url' => $finance->animal_id
                        ? route('panel.animals.show', $finance->animal_id)
                        : null,
                ];
            });

        $summary = $this->buildSummary($filters);
        $categories = FinanceCategory::query()
            ->orderBy('id')
            ->get(['id', 'name']);

        return new FinancesIndexViewModel(
            categories: $categories
                ->map(fn (FinanceCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->all(),
            feeds: Feed::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Feed $feed): array => [
                    'id' => $feed->id,
                    'name' => $feed->name,
                ])
                ->all(),
            animals: Animal::query()
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
                ->map(fn (Animal $animal): array => [
                    'id' => $animal->id,
                    'name' => strip_tags((string) $animal->name),
                ])
                ->all(),
            categoryRows: FinanceCategory::query()
                ->withCount('finances')
                ->orderBy('id')
                ->get()
                ->map(fn (FinanceCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'usage_count' => (int) $category->finances_count,
                    'can_delete' => $category->id > 5 && (int) $category->finances_count === 0,
                ])
                ->all(),
            transactions: $transactions,
            filters: [
                'type' => $filters['type'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
                'title' => $filters['title'] ?? '',
                'amount_from' => $filters['amount_from'] ?? '',
                'amount_to' => $filters['amount_to'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
            ],
            summary: $summary,
            charts: $this->buildCharts($categories, $summary['totals']),
        );
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(($filters['type'] ?? null), fn (Builder $builder, string $type) => $builder->where('type', $type))
            ->when(($filters['category_id'] ?? null), fn (Builder $builder, int $categoryId) => $builder->where('finances_category_id', $categoryId))
            ->when(($filters['title'] ?? null), fn (Builder $builder, string $title) => $builder->where('title', 'like', '%' . $title . '%'))
            ->when(($filters['amount_from'] ?? null) !== null, fn (Builder $builder) => $builder->where('amount', '>=', (float) $filters['amount_from']))
            ->when(($filters['amount_to'] ?? null) !== null, fn (Builder $builder) => $builder->where('amount', '<=', (float) $filters['amount_to']))
            ->when(($filters['date_from'] ?? null), fn (Builder $builder, string $dateFrom) => $builder->whereDate('created_at', '>=', $dateFrom))
            ->when(($filters['date_to'] ?? null), fn (Builder $builder, string $dateTo) => $builder->whereDate('created_at', '<=', $dateTo));
    }

    private function buildSummary(array $filters): array
    {
        $overallIncome = (float) Finance::query()->where('type', 'i')->sum('amount');
        $overallCost = (float) Finance::query()->where('type', 'c')->sum('amount');

        $filteredBase = $this->applyFilters(Finance::query(), $filters);
        $filteredIncome = (clone $filteredBase)->where('type', 'i')->sum('amount');
        $filteredCost = (clone $filteredBase)->where('type', 'c')->sum('amount');
        $filteredCount = (clone $filteredBase)->count();

        return [
            'totals' => [
                'income' => $overallIncome,
                'cost' => $overallCost,
                'balance' => $overallIncome - $overallCost,
                'income_label' => $this->formatCurrency($overallIncome),
                'cost_label' => $this->formatCurrency($overallCost),
                'balance_label' => $this->formatCurrency($overallIncome - $overallCost),
            ],
            'filtered' => [
                'income' => (float) $filteredIncome,
                'cost' => (float) $filteredCost,
                'balance' => (float) $filteredIncome - (float) $filteredCost,
                'count' => (int) $filteredCount,
                'income_label' => $this->formatCurrency((float) $filteredIncome),
                'cost_label' => $this->formatCurrency((float) $filteredCost),
                'balance_label' => $this->formatCurrency((float) $filteredIncome - (float) $filteredCost),
            ],
        ];
    }

    private function buildCharts($categories, array $totals): array
    {
        $incomeByCategory = Finance::query()
            ->selectRaw('finances_category_id, SUM(amount) as total')
            ->where('type', 'i')
            ->groupBy('finances_category_id')
            ->pluck('total', 'finances_category_id');

        $costByCategory = Finance::query()
            ->selectRaw('finances_category_id, SUM(amount) as total')
            ->where('type', 'c')
            ->groupBy('finances_category_id')
            ->pluck('total', 'finances_category_id');

        $labels = $categories->pluck('name')->all();

        return [
            'summary' => [
                'labels' => ['Dochody', 'Koszty'],
                'datasets' => [[
                    'label' => 'Podsumowanie',
                    'data' => [
                        (float) ($totals['income'] ?? 0),
                        (float) ($totals['cost'] ?? 0),
                    ],
                    'backgroundColor' => ['#22c55e', '#ef4444'],
                ]],
            ],
            'income' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Dochody wg kategorii',
                    'data' => $categories->map(fn (FinanceCategory $category): float => (float) ($incomeByCategory[$category->id] ?? 0))->all(),
                    'backgroundColor' => $this->palette(),
                ]],
            ],
            'cost' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Koszty wg kategorii',
                    'data' => $categories->map(fn (FinanceCategory $category): float => (float) ($costByCategory[$category->id] ?? 0))->all(),
                    'backgroundColor' => $this->palette(),
                ]],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function palette(): array
    {
        return [
            '#38bdf8', '#f97316', '#a855f7', '#22c55e', '#eab308', '#ef4444',
            '#14b8a6', '#f43f5e', '#84cc16', '#3b82f6', '#e879f9', '#8b5cf6',
        ];
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' zl';
    }
}
