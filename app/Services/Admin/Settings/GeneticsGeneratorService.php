<?php

namespace App\Services\Admin\Settings;

use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneticsGeneratorService
{
    /**
     * @return array<int, array<int, array{genotype_id:int,name:string,type:string}>>
     */
    public function buildRowsForAnimals(Collection $animals): array
    {
        $lookup = $this->buildLookup();
        $rows = [];

        foreach ($animals as $animal) {
            $rows[$animal->id] = $this->generateFromName((string) $animal->name, $lookup);
        }

        return $rows;
    }

    /**
     * @param Collection<int, Animal> $animals
     * @return array{animals:int, rows:int}
     */
    public function storeGeneratedForAnimals(Collection $animals): array
    {
        $lookup = $this->buildLookup();
        $pendingRows = [];
        $animalsWithRows = 0;

        foreach ($animals as $animal) {
            $generated = $this->generateFromName((string) $animal->name, $lookup);
            if (empty($generated)) {
                continue;
            }

            $animalsWithRows++;
            foreach ($generated as $gene) {
                $pendingRows[] = [
                    'animal_id' => (int) $animal->id,
                    'genotype_id' => (int) $gene['genotype_id'],
                    'type' => (string) $gene['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($pendingRows)) {
            return ['animals' => 0, 'rows' => 0];
        }

        $uniqueRows = [];
        foreach ($pendingRows as $row) {
            $key = $row['animal_id'] . ':' . $row['genotype_id'] . ':' . $row['type'];
            $uniqueRows[$key] = $row;
        }

        $animalIds = array_values(array_unique(array_column($uniqueRows, 'animal_id')));
        $existing = AnimalGenotype::query()
            ->whereIn('animal_id', $animalIds)
            ->get(['animal_id', 'genotype_id', 'type'])
            ->mapWithKeys(fn (AnimalGenotype $row) => [
                ((int) $row->animal_id) . ':' . ((int) $row->genotype_id) . ':' . ((string) $row->type) => true,
            ])
            ->all();

        $rowsToInsert = [];
        foreach ($uniqueRows as $key => $row) {
            if (!isset($existing[$key])) {
                $rowsToInsert[] = $row;
            }
        }

        if (!empty($rowsToInsert)) {
            DB::table('animal_genotype')->insert($rowsToInsert);
        }

        return ['animals' => $animalsWithRows, 'rows' => count($rowsToInsert)];
    }

    /**
     * @return array{
     *   by_first_token: array<string, array<int, array{words:array<int,string>,genes:array<int,array{genotype_id:int,name:string}>,priority:int}>>,
     *   marker_tokens: array<string, string>
     * }
     */
    private function buildLookup(): array
    {
        $firstTokenMap = [];

        $categories = AnimalGenotypeCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $phraseOwners = [];
        foreach ($categories as $category) {
            $normalized = $this->normalizeText((string) $category->name);
            if ($normalized === '') {
                continue;
            }

            $words = explode(' ', $normalized);
            $first = $words[0] ?? null;
            if ($first === null) {
                continue;
            }

            $entry = [
                'words' => $words,
                'genes' => [[
                    'genotype_id' => (int) $category->id,
                    'name' => (string) $category->name,
                ]],
                'priority' => 0,
            ];

            $phraseOwners[$normalized] = 'gene';
            $firstTokenMap[$first][] = $entry;
        }

        $traits = AnimalGenotypeTrait::query()
            ->with('genes.category')
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($traits as $trait) {
            $normalized = $this->normalizeText((string) $trait->name);
            if ($normalized === '' || (($phraseOwners[$normalized] ?? null) === 'gene')) {
                continue;
            }

            $genes = $trait->genes
                ->map(function ($dictionaryRow) {
                    $category = $dictionaryRow->category;
                    if (!$category) {
                        return null;
                    }

                    return [
                        'genotype_id' => (int) $category->id,
                        'name' => (string) $category->name,
                    ];
                })
                ->filter()
                ->unique(fn (array $gene) => $gene['genotype_id'])
                ->values()
                ->all();

            if (empty($genes)) {
                continue;
            }

            $words = explode(' ', $normalized);
            $first = $words[0] ?? null;
            if ($first === null) {
                continue;
            }

            $firstTokenMap[$first][] = [
                'words' => $words,
                'genes' => $genes,
                'priority' => 1,
            ];
        }

        foreach ($firstTokenMap as $firstToken => $entries) {
            usort($entries, function (array $a, array $b): int {
                $lengthCompare = count($b['words']) <=> count($a['words']);
                if ($lengthCompare !== 0) {
                    return $lengthCompare;
                }

                return ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99);
            });

            $firstTokenMap[$firstToken] = $entries;
        }

        return [
            'by_first_token' => $firstTokenMap,
            'marker_tokens' => [
                'het' => 'h',
                'ph' => 'p',
            ],
        ];
    }

    /**
     * @param array{
     *   by_first_token: array<string, array<int, array{words:array<int,string>,genes:array<int,array{genotype_id:int,name:string}>,priority:int}>>,
     *   marker_tokens: array<string, string>
     * } $lookup
     * @return array<int, array{genotype_id:int,name:string,type:string}>
     */
    private function generateFromName(string $animalName, array $lookup): array
    {
        $namePart = $this->extractNamePart($animalName);
        $tokens = $this->tokenize($namePart);
        if (empty($tokens)) {
            return [];
        }

        $markers = $lookup['marker_tokens'] ?? [];
        $entriesByFirst = $lookup['by_first_token'] ?? [];
        $currentType = 'v';
        $result = [];
        $seen = [];
        $i = 0;

        while ($i < count($tokens)) {
            $token = $tokens[$i];
            if (isset($markers[$token])) {
                $currentType = $markers[$token];
                $i++;
                continue;
            }

            $matched = false;
            $candidates = $entriesByFirst[$token] ?? [];
            foreach ($candidates as $candidate) {
                $words = $candidate['words'];
                $wordCount = count($words);
                if ($wordCount === 0 || ($i + $wordCount) > count($tokens)) {
                    continue;
                }

                $slice = array_slice($tokens, $i, $wordCount);
                if ($slice !== $words) {
                    continue;
                }

                foreach ($candidate['genes'] as $gene) {
                    $rowKey = $currentType . ':' . $gene['genotype_id'];
                    if (isset($seen[$rowKey])) {
                        continue;
                    }

                    $seen[$rowKey] = true;
                    $result[] = [
                        'genotype_id' => (int) $gene['genotype_id'],
                        'name' => (string) $gene['name'],
                        'type' => $currentType,
                    ];
                }

                $i += $wordCount;
                $matched = true;
                break;
            }

            if (!$matched) {
                $i++;
            }
        }

        return $result;
    }

    private function extractNamePart(string $animalName): string
    {
        $name = trim($animalName);
        if ($name === '') {
            return '';
        }

        if (str_contains($name, '-')) {
            $parts = explode('-', $name, 2);
            if (isset($parts[1])) {
                return trim($parts[1]);
            }
        }

        return $name;
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\/]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $value): array
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized), fn (string $token) => $token !== ''));
    }
}
