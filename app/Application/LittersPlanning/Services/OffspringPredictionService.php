<?php

namespace App\Application\LittersPlanning\Services;

use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use App\Services\Genetics\GenotypeCalculator;

class OffspringPredictionService
{
    public function __construct(private readonly GenotypeCalculator $genotypeCalculator)
    {
    }

    /**
     * @return array{
     *     female:array{id:int,name:string,animal_type_id:int|null},
     *     male:array{id:int,name:string,animal_type_id:int|null},
     *     rows:array<int, array<string, mixed>>
     * }
     */
    public function handle(int $femaleId, int $maleId): array
    {
        $female = Animal::query()
            ->with(['genotypes.category'])
            ->findOrFail($femaleId);

        $male = Animal::query()
            ->with(['genotypes.category'])
            ->findOrFail($maleId);

        $dictionary = AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                $gene->gene_code,
                $gene->name,
                $gene->gene_type,
            ])
            ->all();

        $rows = $this->genotypeCalculator
            ->setParentsTypeIds($male->animal_type_id, $female->animal_type_id)
            ->getGenotypeFinale(
                $this->buildAnimalGenotypeArray($male),
                $this->buildAnimalGenotypeArray($female),
                $dictionary
            );

        return [
            'female' => [
                'id' => (int) $female->id,
                'name' => $this->normalizeName($female->name),
                'animal_type_id' => $female->animal_type_id !== null ? (int) $female->animal_type_id : null,
            ],
            'male' => [
                'id' => (int) $male->id,
                'name' => $this->normalizeName($male->name),
                'animal_type_id' => $male->animal_type_id !== null ? (int) $male->animal_type_id : null,
            ],
            'rows' => $rows,
        ];
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

    private function normalizeName(?string $value): string
    {
        $name = trim(strip_tags((string) $value));

        return $name !== '' ? $name : '-';
    }
}

