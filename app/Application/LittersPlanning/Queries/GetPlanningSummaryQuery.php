<?php

namespace App\Application\LittersPlanning\Queries;

use App\Application\LittersPlanning\Services\OffspringResultFormatter;
use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Services\Genetics\GenotypeCalculator;

class GetPlanningSummaryQuery
{
    public function __construct(
        private readonly GenotypeCalculator $genotypeCalculator,
        private readonly OffspringResultFormatter $formatter,
    ) {
    }

    /**
     * @param array<int, array{female_id:int,male_id:int}> $pairs
     * @return array<int, array{female_id:int,female_name:string,female_weight:int,male_id:int,male_name:string,male_weight:int,rows:array<int, array<string, mixed>>}>
     */
    public function handle(array $pairs): array
    {
        if (empty($pairs)) {
            return [];
        }

        $animalIds = collect($pairs)
            ->flatMap(fn (array $pair): array => [(int) $pair['female_id'], (int) $pair['male_id']])
            ->unique()
            ->values();

        $animals = Animal::query()
            ->with(['genotypes.category'])
            ->withMax('weights', 'value')
            ->whereIn('id', $animalIds)
            ->get(['id', 'name', 'animal_type_id'])
            ->keyBy('id');

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $result = [];
        foreach ($pairs as $pair) {
            $femaleId = (int) $pair['female_id'];
            $maleId = (int) $pair['male_id'];
            $female = $animals->get($femaleId);
            $male = $animals->get($maleId);
            if (!$female || !$male) {
                continue;
            }

            $calculated = $this->genotypeCalculator
                ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
                ->getGenotypeFinale(
                    $this->buildAnimalGenotypeArray($male),
                    $this->buildAnimalGenotypeArray($female),
                    $dictionary
                );

            $result[] = [
                'female_id' => $femaleId,
                'female_name' => $this->normalizeName($female->name),
                'female_weight' => (int) round((float) ($female->weights_max_value ?? 0)),
                'male_id' => $maleId,
                'male_name' => $this->normalizeName($male->name),
                'male_weight' => (int) round((float) ($male->weights_max_value ?? 0)),
                'rows' => $this->formatter->formatSummary($calculated),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function buildAnimalGenotypeArray(Animal $animal): array
    {
        $result = [];

        foreach ($animal->genotypes as $genotype) {
            $type = strtolower((string) ($genotype->type ?? ''));
            if ($type === 'p' || ($type !== 'h' && $type !== 'v')) {
                continue;
            }

            $category = $genotype->category;
            if (!$category) {
                continue;
            }

            $geneCode = (string) ($category->gene_code ?? '');
            $geneType = strtolower((string) ($category->gene_type ?? ''));
            if ($geneCode === '') {
                continue;
            }

            if ($type === 'h') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
                continue;
            }

            if ($geneType === 'r') {
                $result[] = [lcfirst($geneCode), lcfirst($geneCode)];
            } elseif ($geneType === 'd' || $geneType === 'i') {
                $result[] = [ucfirst($geneCode), lcfirst($geneCode)];
            } else {
                $result[] = [ucfirst($geneCode), ucfirst($geneCode)];
            }
        }

        return $result;
    }

    private function normalizeName(?string $name): string
    {
        $value = trim(strip_tags((string) $name));

        return $value !== '' ? $value : '-';
    }
}
