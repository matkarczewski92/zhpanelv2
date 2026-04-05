<?php

namespace App\Application\Litters\Queries;

use App\Application\Litters\Support\IncubationTimelineBuilder;
use App\Models\Litter;

class GetIncubationIndexQuery
{
    public function __construct(
        private readonly IncubationTimelineBuilder $incubationTimelineBuilder
    ) {
    }

    public function handle(): array
    {
        $litters = Litter::query()
            ->with([
                'maleParent:id,name',
                'femaleParent:id,name',
            ])
            ->whereNotNull('laying_date')
            ->whereNull('hatching_date')
            ->orderBy('laying_date')
            ->orderBy('id')
            ->get([
                'id',
                'litter_code',
                'season',
                'laying_date',
                'hatching_date',
                'laying_eggs_ok',
                'hatching_eggs',
                'parent_male',
                'parent_female',
            ]);

        $rows = $litters
            ->map(function (Litter $litter): array {
                $timeline = $this->incubationTimelineBuilder->buildActiveBoardForLitter($litter);

                return [
                    'litter' => [
                        'id' => (int) $litter->id,
                        'code' => $this->resolveLitterCode($litter),
                        'season' => $litter->season ? (int) $litter->season : null,
                        'female_name' => $this->sanitizeName($litter->femaleParent?->name),
                        'male_name' => $this->sanitizeName($litter->maleParent?->name),
                        'eggs_to_incubation_label' => $this->formatEggCount($litter->laying_eggs_ok),
                        'hatching_eggs_label' => $this->formatEggCount($litter->hatching_eggs),
                        'profile_url' => route('panel.litters.show', $litter->id),
                    ],
                    'timeline' => $timeline,
                ];
            })
            ->sortBy(fn (array $row) => (int) ($row['timeline']['sort_timestamp'] ?? PHP_INT_MAX))
            ->values()
            ->all();

        return [
            'rows' => $rows,
        ];
    }

    private function resolveLitterCode(Litter $litter): string
    {
        $code = trim((string) ($litter->litter_code ?? ''));

        return $code !== '' ? $code : ('L#' . $litter->id);
    }

    private function sanitizeName(?string $value): string
    {
        $name = trim(strip_tags((string) $value));

        return $name !== '' ? $name : '-';
    }

    private function formatEggCount(?int $value): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format((int) $value, 0, ',', ' ') . ' szt.';
    }
}
