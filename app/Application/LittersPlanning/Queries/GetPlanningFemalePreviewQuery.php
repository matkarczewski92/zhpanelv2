<?php

namespace App\Application\LittersPlanning\Queries;

use App\Application\LittersPlanning\Services\OffspringResultFormatter;
use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Services\Genetics\GenotypeCalculator;

class GetPlanningFemalePreviewQuery
{
    public function __construct(
        private readonly GenotypeCalculator $genotypeCalculator,
        private readonly OffspringResultFormatter $formatter,
    ) {
    }

    /**
     * @param array<int, array{female_id:int,male_id:int}> $selectedPairs
     * @return array<int, array{male_id:int,male_name:string,male_weight:int,male_color:string,used_count:int,checked:bool,rows:array<int, array<string, mixed>>}>
     */
    public function handle(int $femaleId, array $selectedPairs): array
    {
        $female = Animal::query()
            ->with(['genotypes.category'])
            ->findOrFail($femaleId);

        $males = Animal::query()
            ->whereIn('animal_category_id', [1, 4])
            ->where('sex', 2)
            ->where('animal_type_id', $female->animal_type_id)
            ->withMax('weights', 'value')
            ->with(['genotypes.category'])
            ->orderBy('id')
            ->get(['id', 'name', 'animal_type_id']);

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $femaleGenes = $this->buildAnimalGenotypeArray($female);

        $rows = [];
        foreach ($males as $male) {
            $maleGenes = $this->buildAnimalGenotypeArray($male);
            $calculated = $this->genotypeCalculator
                ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
                ->getGenotypeFinale($maleGenes, $femaleGenes, $dictionary);

            $formattedRows = $this->formatter->formatSummary($calculated);

            $pairKey = $femaleId . ':' . (int) $male->id;
            $usedCount = collect($selectedPairs)
                ->filter(fn (array $pair): bool => ((int) $pair['male_id']) === (int) $male->id)
                ->count();

            $weight = (int) round((float) ($male->weights_max_value ?? 0));

            $rows[] = [
                'male_id' => (int) $male->id,
                'male_name' => $this->normalizeName($male->name),
                'male_weight' => $weight,
                'male_color' => $weight < 180 ? 'danger' : ($weight < 250 ? 'warning' : 'success'),
                'used_count' => $usedCount,
                'checked' => collect($selectedPairs)->contains(fn (array $pair): bool => ($pair['female_id'] . ':' . $pair['male_id']) === $pairKey),
                'rows' => $formattedRows,
            ];
        }

        return $rows;
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
