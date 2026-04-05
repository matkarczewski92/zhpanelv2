<?php

namespace App\Application\Admin\Queries;

use App\Application\Admin\ViewModels\AdminLitterLabelsViewModel;
use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Models\Litter;

class GetAdminLitterLabelsQuery
{
    public function __construct(
        private readonly LitterStatusResolver $statusResolver,
        private readonly LitterTimelineCalculator $timelineCalculator,
    ) {
    }

    public function handle(array $filters): AdminLitterLabelsViewModel
    {
        $selectedCategoryIds = $filters['category_ids'] ?? [1, 2];

        $categories = collect([1, 2, 3, 4])
            ->map(fn (int $id): array => [
                'id' => $id,
                'name' => $this->statusResolver->categoryLabel($id),
            ])
            ->all();

        $litters = Litter::query()
            ->whereIn('category', $selectedCategoryIds)
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->get()
            ->map(function (Litter $litter): array {
                return [
                    'id' => $litter->id,
                    'litter_code' => $litter->litter_code,
                    'season' => $litter->season,
                    'category_id' => (int) $litter->category,
                    'category_name' => $this->statusResolver->categoryLabel((int) $litter->category),
                    'connection_date' => $this->formatDate($litter->connection_date ?? $litter->planned_connection_date),
                    'laying_date' => $this->formatDate($litter->laying_date),
                    'planned_hatching_date' => $this->formatDate($this->timelineCalculator->estimatedHatchingDate($litter)),
                    'laying_eggs_total' => $litter->laying_eggs_total,
                    'laying_eggs_ok' => $litter->laying_eggs_ok,
                ];
            })
            ->all();

        return new AdminLitterLabelsViewModel(
            litters: $litters,
            categories: $categories,
            selectedCategoryIds: $selectedCategoryIds,
            exportUrl: route('admin.labels.litters.export'),
            title: 'Drukowanie etykiet - mioty',
        );
    }

    private function formatDate(mixed $value): ?string
    {
        return $value?->format('Y-m-d');
    }
}
