<?php

namespace App\Application\Animals\Queries;

use App\Application\Animals\Support\PregnancyTimelineBuilder;
use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class GetPregnantFemalesIndexQuery
{
    public function __construct(
        private readonly PregnancyTimelineBuilder $pregnancyTimelineBuilder
    ) {
    }

    public function handle(): array
    {
        $animals = Animal::query()
            ->with([
                'animalType:id,name',
                'littersAsFemale' => function ($query): void {
                    $query
                        ->with([
                            'maleParent:id,name',
                            'pregnancySheds:id,litter_id,shed_date',
                        ])
                        ->whereIn('category', [1, 3])
                        ->where(function ($dateBuilder): void {
                            $dateBuilder
                                ->whereNotNull('connection_date')
                                ->orWhereNotNull('planned_connection_date');
                        })
                        ->whereNull('laying_date');
                },
            ])
            ->where('sex', Sex::Female->value)
            ->whereHas('littersAsFemale', function ($query): void {
                $query
                    ->whereIn('category', [1, 3])
                    ->where(function ($dateBuilder): void {
                        $dateBuilder
                            ->whereNotNull('connection_date')
                            ->orWhereNotNull('planned_connection_date');
                    })
                    ->whereNull('laying_date');
            })
            ->orderBy('id')
            ->get(['id', 'name', 'second_name', 'sex', 'animal_type_id']);

        $rows = $animals
            ->map(function (Animal $animal): array {
                $timeline = $this->pregnancyTimelineBuilder->buildActiveBoardForAnimal($animal);

                return [
                    'animal' => [
                        'id' => (int) $animal->id,
                        'name_display_html' => $this->buildNameDisplay($animal->second_name, $animal->name),
                        'type_name' => optional($animal->animalType)->name,
                        'profile_url' => route('panel.animals.show', $animal->id),
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

    private function buildNameDisplay(?string $secondName, ?string $name): string
    {
        $main = $this->sanitizeName($name);
        $second = $this->sanitizeName($secondName);

        if ($main === '' && $second === '') {
            return '-';
        }

        if ($second !== '') {
            if ($main === '') {
                return '<i>' . $second . '</i>';
            }

            return '<i>' . $second . '</i> ' . $main;
        }

        return $main;
    }

    private function sanitizeName(?string $value): string
    {
        return trim(strip_tags((string) $value, '<b><i><u>'));
    }
}
