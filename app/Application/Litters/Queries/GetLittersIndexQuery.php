<?php

namespace App\Application\Litters\Queries;

use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Application\Litters\ViewModels\LittersIndexViewModel;
use App\Models\Litter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GetLittersIndexQuery
{
    public function __construct(
        private readonly LitterStatusResolver $statusResolver,
        private readonly LitterTimelineCalculator $timelineCalculator,
        private readonly GetLitterFormOptionsQuery $formOptionsQuery,
    ) {
    }

    public function handle(array $filters): LittersIndexViewModel
    {
        $actualLitters = $this->paginateByCategory(1, $filters, 'actual_page', 15);
        $plannedLitters = $this->paginateByCategory(2, $filters, 'planned_page', 20);
        $closedLitters = $this->paginateByCategory(4, $filters, 'closed_page', 15);

        $form = $this->formOptionsQuery->handle();

        $plannedSeasons = Litter::query()
            ->where('category', 2)
            ->whereNotNull('season')
            ->select('season')
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season')
            ->map(fn (mixed $season): int => (int) $season)
            ->all();

        return new LittersIndexViewModel(
            actualLitters: $actualLitters,
            plannedLitters: $plannedLitters,
            closedLitters: $closedLitters,
            maleParents: $form->maleParents,
            femaleParents: $form->femaleParents,
            plannedSeasons: $plannedSeasons,
            categories: $form->categories,
            filters: [
                'q' => $filters['q'] ?? '',
                'season' => $filters['season'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            counts: [
                'actual' => $actualLitters->total(),
                'planned' => $plannedLitters->total(),
                'closed' => $closedLitters->total(),
            ],
        );
    }

    private function paginateByCategory(int $category, array $filters, string $pageName, int $perPage): LengthAwarePaginator
    {
        $query = Litter::query()
            ->with([
                'maleParent:id,name',
                'femaleParent:id,name',
            ])
            ->where('category', $category);

        $this->applyFilters($query, $filters);

        if ($category === 2) {
            $query->orderByRaw('COALESCE(connection_date, planned_connection_date) ASC');
        } elseif ($category === 4) {
            $query->orderByDesc('season')->orderByDesc('id');
        } else {
            $query->orderBy('connection_date')->orderBy('id');
        }

        return $query
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (Litter $litter): array => $this->mapLitterRow($litter));
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['q'] ?? null, function (Builder $builder, string $search): void {
            $builder->where(function (Builder $nested) use ($search): void {
                $nested
                    ->where('litter_code', 'like', '%' . $search . '%')
                    ->orWhereHas('maleParent', fn (Builder $male) => $male->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('femaleParent', fn (Builder $female) => $female->where('name', 'like', '%' . $search . '%'));
            });
        });

        $query->when($filters['season'] ?? null, fn (Builder $builder, int $season) => $builder->where('season', $season));

        $this->statusResolver->applyStatusFilter($query, $filters['status'] ?? null);
    }

    private function mapLitterRow(Litter $litter): array
    {
        $connectionDate = $litter->connection_date ?? $litter->planned_connection_date;
        $estimatedLayingDate = $this->timelineCalculator->estimatedLayingDate($litter);
        $estimatedHatchingDate = $this->timelineCalculator->estimatedHatchingDate($litter);

        return [
            'id' => $litter->id,
            'litter_code' => $litter->litter_code,
            'season' => $litter->season,
            'category' => $litter->category,
            'category_label' => $this->statusResolver->categoryLabel((int) $litter->category),
            'status_label' => $this->statusResolver->statusLabel($litter),
            'connection_date' => $connectionDate?->format('Y-m-d'),
            'laying_date' => $litter->laying_date?->format('Y-m-d'),
            'hatching_date' => $litter->hatching_date?->format('Y-m-d'),
            'estimated_laying_date' => $estimatedLayingDate?->format('Y-m-d'),
            'estimated_hatching_date' => $estimatedHatchingDate?->format('Y-m-d'),
            'male_parent_id' => $litter->parent_male,
            'male_parent_name' => $this->cleanName($litter->maleParent?->name),
            'female_parent_id' => $litter->parent_female,
            'female_parent_name' => $this->cleanName($litter->femaleParent?->name),
            'show_url' => route('panel.litters.show', $litter),
            'edit_url' => route('panel.litters.edit', $litter),
        ];
    }

    private function cleanName(?string $value): string
    {
        $name = trim(strip_tags((string) $value));
        return $name !== '' ? $name : '-';
    }
}
