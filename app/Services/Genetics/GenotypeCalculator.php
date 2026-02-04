<?php

namespace App\Services\Genetics;

use App\Models\AnimalGenotypeException;
use App\Models\AnimalGenotypeTraits;
use App\Services\Genetics\GeneTypeHandlers\DominantGeneTypeHandler;
use App\Services\Genetics\GeneTypeHandlers\GeneTypeHandler;
use App\Services\Genetics\GeneTypeHandlers\IncompleteDominantGeneTypeHandler;
use App\Services\Genetics\GeneTypeHandlers\LineBredGeneTypeHandler;
use App\Services\Genetics\GeneTypeHandlers\PolygenicGeneTypeHandler;
use App\Services\Genetics\GeneTypeHandlers\RecessiveGeneTypeHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GenotypeCalculator
{
    private array $geneTypeHandlers = [];
    private ?int $speciesId = null;
    private ?bool $allowExceptions = null;
    private array $exceptionNameToCode = [];

    public function __construct()
    {
        $this->geneTypeHandlers = [
            'r' => new RecessiveGeneTypeHandler(),
            'd' => new DominantGeneTypeHandler(),
            'i' => new IncompleteDominantGeneTypeHandler(),
            'p' => new PolygenicGeneTypeHandler(),
            'l' => new LineBredGeneTypeHandler(),
        ];
    }

    public function setSpeciesId(?int $speciesId): self
    {
        $this->speciesId = $speciesId;

        return $this;
    }

    public function setParentsTypeIds(?int $maleTypeId, ?int $femaleTypeId): self
    {
        $this->allowExceptions = ($maleTypeId === 1 || $femaleTypeId === 1);
        if ($this->allowExceptions === true && $this->speciesId === null) {
            $this->speciesId = 1;
        }

        return $this;
    }

    public function getGenotypeFinale($maleGens, $femaleGens, $dictionary, $genotypeTraitsDictionary = null): array
    {
        $maleGenes = (array) $maleGens;
        $femaleGenes = (array) $femaleGens;
        $dictionaryMaps = $this->normalizeDictionary($dictionary);
        $traitsDictionary = $genotypeTraitsDictionary ?? $this->getGenotypeTraitsDictionary();
        $this->exceptionNameToCode = $dictionaryMaps['by_name'];

        $geneData = $this->buildGeneData(
            $maleGenes,
            $femaleGenes,
            $dictionaryMaps['by_code']
        );

        $ultramelContext = $this->buildAllelicPairContext($maleGenes, $femaleGenes, 'am', 'ui');
        $motleyStripeContext = $this->buildAllelicPairContext($maleGenes, $femaleGenes, 'mo', 'st');

        $perGeneOutcomes = [];
        foreach ($geneData as $gene) {
            $perGeneOutcomes[] = $this->buildPunnettOutcomesForGene($gene);
        }

        $combinations = $this->combineGeneOutcomes($perGeneOutcomes);
        if (empty($combinations)) {
            $combinations = [[
                'visual_traits' => [],
                'carrier_traits' => [],
                'gene_states' => [],
                'homo_count' => 0,
                'probability' => 1.0,
            ]];
        }

        $exceptions = $this->allowExceptions === true ? $this->loadExceptions() : collect();
        $exceptionLabels = $this->extractExceptionLabels($exceptions);
        $filteredExceptions = $exceptions->isNotEmpty()
            ? $this->filterUltramelExceptions($exceptions, $dictionaryMaps['by_name'])
            : $exceptions;
        $grouped = [];

        foreach ($combinations as $combo) {
            $visualTraits = $combo['visual_traits'];
            $carrierTraits = $combo['carrier_traits'];
            $geneStates = $combo['gene_states'];

            $carrierTraits = $this->applyAmelUltraExclusion($carrierTraits, $geneStates, $dictionaryMaps['by_code']);

            if ($filteredExceptions->isNotEmpty()) {
                $visualTraits = $this->applyExceptionsToTraits($visualTraits, $geneStates, $filteredExceptions);
            }

            $visualTraits = $this->sortTraits($visualTraits);
            $groupKey = implode('||', $visualTraits);

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'visual_traits' => $visualTraits,
                    'carrier_probabilities' => [],
                    'probability' => 0.0,
                    'homo_count' => 0,
                ];
            }

            $grouped[$groupKey]['probability'] += $combo['probability'];
            $grouped[$groupKey]['homo_count'] = max($grouped[$groupKey]['homo_count'], $combo['homo_count']);

            foreach ($carrierTraits as $carrierName) {
                $grouped[$groupKey]['carrier_probabilities'][$carrierName] = ($grouped[$groupKey]['carrier_probabilities'][$carrierName] ?? 0)
                    + $combo['probability'];
            }
        }

        if ($filteredExceptions->isNotEmpty()) {
            $grouped = $this->applyExceptionsToGroupedResults($grouped, $filteredExceptions, $dictionaryMaps['by_name']);
        }
        if ($this->allowExceptions === true) {
            $grouped = $this->applyUltramelRules($grouped, $dictionaryMaps['by_code'], $ultramelContext);
            $grouped = $this->applyMotleyStripeRules($grouped, $dictionaryMaps['by_code'], $motleyStripeContext);
        }

        $rows = [];

        foreach ($grouped as $group) {
            $probability = $group['probability'];
            if ($probability <= 0) {
                continue;
            }

            $carrierProbabilities = $this->applyCombinedCarrierCleanup(
                $group['carrier_probabilities'] ?? [],
                (float) $probability,
                $dictionaryMaps['by_code']
            );
            $carrierLabels = [];
            foreach ($carrierProbabilities as $name => $carrierProbability) {
                $ratio = $carrierProbability / $probability;
                $carrierLabels[] = $this->formatCarrierLabel($name, $ratio * 100);
            }

            $traits = array_merge($group['visual_traits'], $this->sortTraits($carrierLabels));
            $traits = $this->uniqueListPreserveOrder($traits);

            $groupForName = $group;
            $groupForName['carrier_probabilities'] = $carrierProbabilities;
            $displayName = $this->resolveDisplayName(
                $groupForName,
                $traitsDictionary,
                $filteredExceptions,
                $dictionaryMaps['by_name'],
                $exceptionLabels
            );

            $rows[] = [
                'traits' => $traits,
                'visual_traits' => $group['visual_traits'],
                'carrier_traits' => $this->sortTraits($carrierLabels),
                'traits_count' => count($traits),
                'homo_count' => $group['homo_count'],
                'count' => 0,
                'percentage' => $probability * 100,
                'traits_name' => $displayName,
                'main_genes' => '',
                'additional_genes' => '',
                'dominant' => '',
            ];
        }

        $rows = $this->verifyDisplayNamesAgainstFinalTraits(
            $rows,
            $traitsDictionary,
            $filteredExceptions,
            $dictionaryMaps['by_name'],
            $exceptionLabels
        );

        usort($rows, function (array $a, array $b): int {
            if ($a['homo_count'] !== $b['homo_count']) {
                return $b['homo_count'] <=> $a['homo_count'];
            }

            return $b['percentage'] <=> $a['percentage'];
        });

        return $rows;
    }

    private function normalizeDictionary($dictionary): array
    {
        $byCode = [];
        $byName = [];

        foreach ((array) $dictionary as $entry) {
            $code = null;
            $name = null;
            $type = null;

            if (is_array($entry)) {
                $code = $entry['gene_code'] ?? $entry['code'] ?? $entry[0] ?? null;
                $name = $entry['name'] ?? $entry[1] ?? null;
                $type = $entry['gene_type'] ?? $entry['type'] ?? $entry[2] ?? null;
            } elseif (is_object($entry)) {
                $code = $entry->gene_code ?? null;
                $name = $entry->name ?? null;
                $type = $entry->gene_type ?? null;
            }

            if (!$code && !$name) {
                continue;
            }

            $code = (string) ($code ?? $name);
            $name = (string) ($name ?? $code);
            $codeKey = strtolower($code);

            $byCode[$codeKey] = [
                'code' => $code,
                'name' => $name,
                'type' => $type ? strtolower((string) $type) : null,
            ];

            if ($name !== '') {
                $byName[strtolower($name)] = $codeKey;
            }
        }

        return [
            'by_code' => $byCode,
            'by_name' => $byName,
        ];
    }

    private function buildGeneData(array $maleGens, array $femaleGens, array $dictionaryByCode): array
    {
        $maleMap = $this->mapParentGenes($maleGens);
        $femaleMap = $this->mapParentGenes($femaleGens);

        $codes = array_unique(array_merge(array_keys($maleMap), array_keys($femaleMap)));
        $genes = [];

        foreach ($codes as $code) {
            $info = $dictionaryByCode[$code] ?? [
                'code' => $code,
                'name' => $code,
                'type' => null,
            ];

            $geneType = $this->normalizeGeneType($info['type'] ?? null);

            $maleAlleles = $maleMap[$code] ?? $this->buildNormalAlleles($info['code'], $geneType);
            $femaleAlleles = $femaleMap[$code] ?? $this->buildNormalAlleles($info['code'], $geneType);

            $genes[] = [
                'code' => $code,
                'name' => $info['name'],
                'gene_type' => $geneType,
                'male' => $maleAlleles,
                'female' => $femaleAlleles,
            ];
        }

        return $genes;
    }

    private function mapParentGenes(array $gens): array
    {
        $map = [];

        foreach ($gens as $pair) {
            if (!is_array($pair) || count($pair) < 2) {
                continue;
            }

            $code = strtolower((string) ($pair[0] ?? ''));
            if ($code === '') {
                continue;
            }

            $map[$code] = [$pair[0], $pair[1]];
        }

        return $map;
    }

    private function buildAllelicPairContext(
        array $maleGens,
        array $femaleGens,
        string $leftCode,
        string $rightCode
    ): array {
        $maleMap = $this->mapParentGenes($maleGens);
        $femaleMap = $this->mapParentGenes($femaleGens);

        $male = [
            'left' => $this->resolveRecessiveZygosity($maleMap, $leftCode),
            'right' => $this->resolveRecessiveZygosity($maleMap, $rightCode),
        ];
        $female = [
            'left' => $this->resolveRecessiveZygosity($femaleMap, $leftCode),
            'right' => $this->resolveRecessiveZygosity($femaleMap, $rightCode),
        ];

        return [
            'male' => $male,
            'female' => $female,
            'pattern' => $this->detectAllelicPairPattern($male, $female),
        ];
    }

    private function resolveRecessiveZygosity(array $parentMap, string $code): string
    {
        $code = strtolower(trim($code));
        if ($code === '' || !isset($parentMap[$code])) {
            return 'normal';
        }

        $alleles = $parentMap[$code];
        if (!is_array($alleles) || count($alleles) < 2) {
            return 'normal';
        }

        $mutantCount = 0;
        foreach ([$alleles[0], $alleles[1]] as $allele) {
            $firstChar = (string) ($allele[0] ?? '');
            if ($firstChar !== '' && ctype_lower($firstChar)) {
                $mutantCount++;
            }
        }

        if ($mutantCount >= 2) {
            return 'hom';
        }

        return $mutantCount === 1 ? 'het' : 'normal';
    }

    private function detectAllelicPairPattern(array $male, array $female): string
    {
        $isNormal = fn (array $parent): bool => $parent['left'] === 'normal' && $parent['right'] === 'normal';
        $isHetBoth = fn (array $parent): bool => $parent['left'] === 'het' && $parent['right'] === 'het';
        $isHetLeftOnly = fn (array $parent): bool => $parent['left'] === 'het' && $parent['right'] === 'normal';
        $isHetRightOnly = fn (array $parent): bool => $parent['left'] === 'normal' && $parent['right'] === 'het';
        $isHomLeftOnly = fn (array $parent): bool => $parent['left'] === 'hom' && $parent['right'] === 'normal';
        $isHomRightOnly = fn (array $parent): bool => $parent['left'] === 'normal' && $parent['right'] === 'hom';

        if (($isHetBoth($male) && $isNormal($female)) || ($isHetBoth($female) && $isNormal($male))) {
            return 'double_het_vs_normal';
        }

        if (($isHetLeftOnly($male) && $isHetRightOnly($female)) || ($isHetRightOnly($male) && $isHetLeftOnly($female))) {
            return 'split_hets';
        }

        if (($isHomLeftOnly($male) && $isHomRightOnly($female)) || ($isHomRightOnly($male) && $isHomLeftOnly($female))) {
            return 'hom_vs_hom';
        }

        if (
            ($isHomLeftOnly($male) && $isHetRightOnly($female))
            || ($isHetRightOnly($male) && $isHomLeftOnly($female))
            || ($isHomRightOnly($male) && $isHetLeftOnly($female))
            || ($isHetLeftOnly($male) && $isHomRightOnly($female))
        ) {
            return 'hom_vs_het';
        }

        return 'other';
    }

    private function buildNormalAlleles(string $code, string $geneType): array
    {
        $code = (string) $code;

        if ($geneType === 'r') {
            $allele = ucfirst($code);
            return [$allele, $allele];
        }

        $allele = lcfirst($code);
        return [$allele, $allele];
    }

    private function buildPunnettOutcomesForGene(array $gene): array
    {
        $handler = $this->getGeneTypeHandler($gene['gene_type']);
        $male = $gene['male'];
        $female = $gene['female'];

        $combos = [
            [$male[0], $female[0]],
            [$male[0], $female[1]],
            [$male[1], $female[0]],
            [$male[1], $female[1]],
        ];

        $outcomes = [];
        foreach ($combos as $alleles) {
            $mutantCount = 0;
            foreach ($alleles as $allele) {
                if ($handler->isMutantAllele((string) $allele)) {
                    $mutantCount++;
                }
            }

            $zygosity = $mutantCount === 2 ? 'hom' : ($mutantCount === 1 ? 'het' : 'normal');
            $visualTrait = null;
            $carrierTrait = null;
            $homoCount = 0;

            if ($gene['gene_type'] === 'r') {
                if ($zygosity === 'hom') {
                    $visualTrait = $gene['name'];
                    $homoCount = 1;
                } elseif ($zygosity === 'het') {
                    $carrierTrait = $gene['name'];
                }
            } elseif ($gene['gene_type'] === 'i') {
                if ($zygosity === 'hom') {
                    $visualTrait = $this->prefixSuperLabel($gene['name']);
                    $homoCount = 1;
                } elseif ($zygosity === 'het') {
                    $visualTrait = $gene['name'];
                }
            } else {
                if ($zygosity === 'hom') {
                    $visualTrait = $this->prefixSuperLabel($gene['name']);
                    $homoCount = 1;
                } elseif ($zygosity === 'het') {
                    $visualTrait = $gene['name'];
                }
            }

            $key = implode('|', [
                $visualTrait ?? '',
                $carrierTrait ?? '',
                $zygosity,
            ]);

            if (!isset($outcomes[$key])) {
                $outcomes[$key] = [
                    'visual_trait' => $visualTrait,
                    'carrier_trait' => $carrierTrait,
                    'gene_state' => [
                        'gene_code' => $gene['code'],
                        'gene_name' => $gene['name'],
                        'zygosity' => $zygosity,
                    ],
                    'homo_count' => $homoCount,
                    'weight' => 0,
                ];
            }

            $outcomes[$key]['weight']++;
        }

        $results = [];
        foreach ($outcomes as $outcome) {
            $results[] = [
                'visual_trait' => $outcome['visual_trait'],
                'carrier_trait' => $outcome['carrier_trait'],
                'gene_state' => $outcome['gene_state'],
                'homo_count' => $outcome['homo_count'],
                'probability' => $outcome['weight'] / 4,
            ];
        }

        return $results;
    }

    private function combineGeneOutcomes(array $perGeneOutcomes): array
    {
        if (empty($perGeneOutcomes)) {
            return [];
        }

        $results = [[
            'visual_traits' => [],
            'carrier_traits' => [],
            'gene_states' => [],
            'homo_count' => 0,
            'probability' => 1.0,
        ]];

        foreach ($perGeneOutcomes as $geneOutcomes) {
            $next = [];
            foreach ($results as $base) {
                foreach ($geneOutcomes as $outcome) {
                    $visualTraits = $base['visual_traits'];
                    if ($outcome['visual_trait']) {
                        $visualTraits[] = $outcome['visual_trait'];
                    }

                    $carrierTraits = $base['carrier_traits'];
                    if ($outcome['carrier_trait']) {
                        $carrierTraits[] = $outcome['carrier_trait'];
                    }

                    $next[] = [
                        'visual_traits' => $visualTraits,
                        'carrier_traits' => $carrierTraits,
                        'gene_states' => array_merge($base['gene_states'], [$outcome['gene_state']]),
                        'homo_count' => $base['homo_count'] + $outcome['homo_count'],
                        'probability' => $base['probability'] * $outcome['probability'],
                    ];
                }
            }

            $results = $next;
        }

        return $results;
    }

    private function sortTraits(array $traits): array
    {
        $traits = array_values(array_filter($traits, fn ($trait) => trim((string) $trait) !== ''));
        sort($traits, SORT_NATURAL | SORT_FLAG_CASE);

        return $traits;
    }

    private function applyAmelUltraExclusion(array $carrierTraits, array $geneStates, array $dictionaryByCode): array
    {
        if (empty($carrierTraits) || empty($geneStates)) {
            return $carrierTraits;
        }

        $carrierTraits = $this->applyAllelicCarrierExclusion($carrierTraits, $geneStates, $dictionaryByCode, 'am', 'ui');
        $carrierTraits = $this->applyAllelicCarrierExclusion($carrierTraits, $geneStates, $dictionaryByCode, 'mo', 'st');

        return $carrierTraits;
    }

    private function applyAllelicCarrierExclusion(
        array $carrierTraits,
        array $geneStates,
        array $dictionaryByCode,
        string $leftCode,
        string $rightCode
    ): array {
        if (empty($carrierTraits) || empty($geneStates)) {
            return $carrierTraits;
        }

        $leftName = $dictionaryByCode[$leftCode]['name'] ?? $leftCode;
        $rightName = $dictionaryByCode[$rightCode]['name'] ?? $rightCode;

        $leftState = $this->findGeneState($geneStates, $leftCode, (string) $leftName);
        $rightState = $this->findGeneState($geneStates, $rightCode, (string) $rightName);

        if ($leftState && $leftState['zygosity'] === 'hom' && $rightState && $rightState['zygosity'] === 'het') {
            return $this->removeCarrierByName($carrierTraits, (string) ($rightName ?: ($rightState['gene_name'] ?? $rightCode)));
        }

        if ($rightState && $rightState['zygosity'] === 'hom' && $leftState && $leftState['zygosity'] === 'het') {
            return $this->removeCarrierByName($carrierTraits, (string) ($leftName ?: ($leftState['gene_name'] ?? $leftCode)));
        }

        return $carrierTraits;
    }

    private function findGeneState(array $geneStates, string $code, string $name): ?array
    {
        $code = strtolower($code);
        $name = strtolower($name);

        foreach ($geneStates as $state) {
            $stateCode = strtolower((string) ($state['gene_code'] ?? ''));
            $stateName = strtolower((string) ($state['gene_name'] ?? ''));

            if ($stateCode === $code || $stateName === $name) {
                return $state;
            }
        }

        return null;
    }

    private function removeCarrierByName(array $carrierTraits, string $name): array
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return $carrierTraits;
        }

        return array_values(array_filter($carrierTraits, function ($trait) use ($name) {
            return strtolower(trim((string) $trait)) !== $name;
        }));
    }

    private function matchTraitSet(array $visualTraits, array $traitsDictionary, array $exceptionLabels = []): ?string
    {
        $visualTraits = array_values(array_filter(array_map('trim', $visualTraits), fn ($value) => $value !== ''));
        if (empty($visualTraits)) {
            return null;
        }

        if (empty($traitsDictionary)) {
            return $this->buildNameFromExceptionLabels($visualTraits, $exceptionLabels);
        }

        $bestMatch = null;
        $usedTraits = [];

        foreach ($traitsDictionary as $traitGroup) {
            foreach ($traitGroup as $traitName => $requiredTraits) {
                $matched = array_intersect($visualTraits, $requiredTraits);
                if (count($matched) === count($requiredTraits)) {
                    $bestMatch = $traitName;
                    $usedTraits = $matched;
                    break 2;
                }
            }
        }

        $unusedTraits = array_values(array_diff($visualTraits, $usedTraits));

        if ($bestMatch) {
            if (count($usedTraits) === 1 && !empty($unusedTraits)) {
                return $this->buildNameFromExceptionLabels($visualTraits, $exceptionLabels);
            }

            $parts = array_merge([$bestMatch], $unusedTraits);
            return implode(' ', $parts);
        }

        return $this->buildNameFromExceptionLabels($visualTraits, $exceptionLabels);
    }

    private function resolveDisplayName(
        array $group,
        array $traitsDictionary,
        Collection $exceptions,
        array $nameToCodeMap,
        array $exceptionLabels
    ): ?string {
        $visualTraits = $group['visual_traits'] ?? [];

        if ($exceptions->isNotEmpty()) {
            $state = $this->buildGroupState($visualTraits, $group['carrier_probabilities'] ?? [], $nameToCodeMap);
            $visualTraits = $this->applyExceptionsToTraits($visualTraits, $state, $exceptions);
        }

        return $this->matchTraitSet($visualTraits, $traitsDictionary, $exceptionLabels);
    }

    private function verifyDisplayNamesAgainstFinalTraits(
        array $rows,
        array $traitsDictionary,
        Collection $exceptions,
        array $nameToCodeMap,
        array $exceptionLabels
    ): array {
        foreach ($rows as $index => $row) {
            $group = [
                'visual_traits' => $row['visual_traits'] ?? [],
                'carrier_probabilities' => $this->buildCarrierProbabilityMapFromLabels($row['carrier_traits'] ?? []),
            ];

            $resolvedName = $this->resolveDisplayName(
                $group,
                $traitsDictionary,
                $exceptions,
                $nameToCodeMap,
                $exceptionLabels
            );

            if ($resolvedName === null || trim($resolvedName) === '') {
                $visualTraits = $row['visual_traits'] ?? [];
                $resolvedName = empty($visualTraits) ? null : implode(' ', $visualTraits);
            }

            $rows[$index]['traits_name'] = $this->normalizeDisplayNameOrder(
                $this->replaceCaramelUltramelWithGoldDust($resolvedName)
            );
        }

        return $rows;
    }

    private function buildCarrierProbabilityMapFromLabels(array $carrierLabels): array
    {
        $carrierProbabilities = [];

        foreach ($carrierLabels as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }

            if (!preg_match('/(?:[\\d.,]+%\\s+)?het\\s+(.+)$/i', $label, $matches)) {
                continue;
            }

            $geneName = trim((string) ($matches[1] ?? ''));
            if ($geneName === '') {
                continue;
            }

            $carrierProbabilities[$geneName] = 1.0;
        }

        return $carrierProbabilities;
    }

    private function replaceCaramelUltramelWithGoldDust(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $hasCaramel = preg_match('/\\bcaramel\\b/i', $name) === 1;
        $hasUltramel = preg_match('/\\bultramel\\b/i', $name) === 1;

        if (!$hasCaramel || !$hasUltramel) {
            return $name;
        }

        $withoutLabels = preg_replace('/\\b(?:caramel|ultramel)\\b/i', '', $name);
        $withoutLabels = trim((string) preg_replace('/\\s+/', ' ', (string) $withoutLabels));

        if ($withoutLabels === '') {
            return 'Gold Dust';
        }

        if (preg_match('/\\bgold\\s+dust\\b/i', $withoutLabels) === 1) {
            return $withoutLabels;
        }

        return "Gold Dust {$withoutLabels}";
    }

    private function normalizeDisplayNameOrder(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $hasGoldDust = preg_match('/\\bgold\\s+dust\\b/i', $name) === 1;
        $hasUltramel = preg_match('/\\bultramel\\b/i', $name) === 1;
        $hasMotleyStripe = preg_match('/\\bmotley\\/stripe\\b/i', $name) === 1;

        $remaining = $name;
        $remaining = preg_replace('/\\bgold\\s+dust\\b/i', '', (string) $remaining);
        $remaining = preg_replace('/\\bultramel\\b/i', '', (string) $remaining);
        $remaining = preg_replace('/\\bmotley\\/stripe\\b/i', '', (string) $remaining);
        $remaining = trim((string) preg_replace('/\\s+/', ' ', (string) $remaining));

        $parts = [];

        if ($hasGoldDust) {
            $parts[] = 'Gold Dust';
        } elseif ($hasUltramel) {
            $parts[] = 'Ultramel';
        }

        if ($hasMotleyStripe) {
            $parts[] = 'Motley/Stripe';
        }

        if ($remaining !== '') {
            $parts[] = $remaining;
        }

        return implode(' ', $parts);
    }

    private function buildNameFromExceptionLabels(array $visualTraits, array $exceptionLabels): ?string
    {
        if (empty($exceptionLabels) || empty($visualTraits)) {
            return null;
        }

        $traits = array_values($visualTraits);
        $labelsLower = array_map('strtolower', $exceptionLabels);
        $madeExceptionName = false;

        $hasUltramel = $this->arrayContainsLabel($traits, 'Ultramel');
        $hasCaramel = $this->arrayContainsLabel($traits, 'Caramel');
        if ($hasUltramel && $hasCaramel) {
            $traits = $this->removeLabel($traits, 'Ultramel');
            $traits = $this->removeLabel($traits, 'Caramel');
            array_unshift($traits, 'Gold Dust');
            $madeExceptionName = true;
        }

        if ($madeExceptionName) {
            return implode(' ', $traits);
        }

        $picked = [];
        $remaining = [];

        foreach ($traits as $trait) {
            $traitKey = strtolower((string) $trait);
            if (in_array($traitKey, $labelsLower, true)) {
                $picked[] = $trait;
            } else {
                $remaining[] = $trait;
            }
        }

        if (empty($picked)) {
            return null;
        }

        $parts = array_merge($picked, $remaining);
        return implode(' ', $parts);
    }

    private function arrayContainsLabel(array $traits, string $label): bool
    {
        $needle = strtolower(trim($label));
        if ($needle === '') {
            return false;
        }

        foreach ($traits as $trait) {
            if (strtolower(trim((string) $trait)) === $needle) {
                return true;
            }
        }

        return false;
    }

    private function removeLabel(array $traits, string $label): array
    {
        $needle = strtolower(trim($label));
        if ($needle === '') {
            return $traits;
        }

        $filtered = [];
        foreach ($traits as $trait) {
            if (strtolower(trim((string) $trait)) === $needle) {
                continue;
            }
            $filtered[] = $trait;
        }

        return $filtered;
    }

    private function extractExceptionLabels(Collection $exceptions): array
    {
        if ($exceptions->isEmpty()) {
            return [];
        }

        $labels = [];

        foreach ($exceptions as $exception) {
            $effect = $exception->effect_json ?? [];
            $action = strtolower(trim((string) ($effect['action'] ?? '')));
            $target = strtolower(trim((string) ($effect['target'] ?? '')));

            if ($target !== 'homozygote_label') {
                continue;
            }

            if ($action === 'add_label' || $action === 'set_label') {
                $value = trim((string) ($effect['value'] ?? ''));
                if ($value !== '') {
                    $labels[] = $value;
                }
            } elseif ($action === 'replace_label') {
                $to = trim((string) ($effect['to'] ?? ''));
                if ($to !== '') {
                    $labels[] = $to;
                }
            }
        }

        return $this->uniqueListPreserveOrder($labels);
    }

    private function getGenotypeTraitsDictionary(): array
    {
        if (!Schema::hasTable('animal_genotype_traits') || !Schema::hasTable('animal_genotype_traits_dictionary')) {
            return [];
        }

        $traits = AnimalGenotypeTraits::orderBy('number_of_traits')->get();
        $array = [];

        foreach ($traits as $trait) {
            foreach ($trait->getTraitsDictionary as $tr) {
                $category = $tr->genotypeCategory;
                if (!$category) {
                    continue;
                }

                $array[$trait->number_of_traits][$trait->name][] = $category->name;
            }
        }

        krsort($array);

        return $array;
    }

    private function loadExceptions(): Collection
    {
        if (!Schema::hasTable('animal_genotype_exceptions')) {
            return collect();
        }

        $query = AnimalGenotypeException::query()
            ->where('is_enabled', true)
            ->orderBy('priority', 'asc');

        if ($this->speciesId !== null) {
            $speciesId = $this->speciesId;
            $query->where(function ($builder) use ($speciesId) {
                $builder->whereNull('species_id')
                    ->orWhere('species_id', $speciesId);
            });
        } else {
            $query->whereNull('species_id');
        }

        return $query->get();
    }

    private function applyExceptionsToTraits(array $visualTraits, array $state, Collection $exceptions): array
    {
        foreach ($exceptions as $exception) {
            $requirements = $exception->match_json ?? [];
            if (!$this->matchesException($state, $requirements)) {
                continue;
            }

            $visualTraits = $this->applyExceptionEffectToTraits($visualTraits, $exception->effect_json ?? []);
        }

        return $visualTraits;
    }

    private function applyExceptionsToGroupedResults(array $grouped, Collection $exceptions, array $nameToCodeMap): array
    {
        $normalized = [];

        foreach ($grouped as $group) {
            $state = $this->buildGroupState($group['visual_traits'], $group['carrier_probabilities'], $nameToCodeMap);
            $visualTraits = $this->applyExceptionsToTraits($group['visual_traits'], $state, $exceptions);
            $visualTraits = $this->sortTraits($visualTraits);
            $key = implode('||', $visualTraits);

            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'visual_traits' => $visualTraits,
                    'carrier_probabilities' => [],
                    'probability' => 0.0,
                    'homo_count' => 0,
                ];
            }

            $normalized[$key]['probability'] += $group['probability'];
            $normalized[$key]['homo_count'] = max($normalized[$key]['homo_count'], $group['homo_count']);

            foreach ($group['carrier_probabilities'] as $name => $probability) {
                $normalized[$key]['carrier_probabilities'][$name] = ($normalized[$key]['carrier_probabilities'][$name] ?? 0)
                    + $probability;
            }
        }

        return $normalized;
    }

    private function filterUltramelExceptions(Collection $exceptions, array $nameToCodeMap): Collection
    {
        if ($exceptions->isEmpty()) {
            return $exceptions;
        }

        return $exceptions->reject(function ($exception) use ($nameToCodeMap) {
            return $this->isUltramelException($exception->match_json ?? [], $exception->effect_json ?? [], $nameToCodeMap)
                || $this->isMotleyStripeException($exception->match_json ?? [], $exception->effect_json ?? [], $nameToCodeMap);
        });
    }

    private function isUltramelException(array $requirements, array $effect, array $nameToCodeMap): bool
    {
        return $this->isAllelicLabelException(
            $requirements,
            $effect,
            $nameToCodeMap,
            'ultramel',
            ['am', 'ui']
        );
    }

    private function isMotleyStripeException(array $requirements, array $effect, array $nameToCodeMap): bool
    {
        return $this->isAllelicLabelException(
            $requirements,
            $effect,
            $nameToCodeMap,
            'motley/stripe',
            ['mo', 'st']
        );
    }

    private function isAllelicLabelException(
        array $requirements,
        array $effect,
        array $nameToCodeMap,
        string $expectedLabel,
        array $requiredCodes
    ): bool {
        $target = strtolower(trim((string) ($effect['target'] ?? '')));
        if ($target !== 'homozygote_label') {
            return false;
        }

        $action = strtolower(trim((string) ($effect['action'] ?? '')));
        $value = '';

        if ($action === 'add_label' || $action === 'set_label') {
            $value = strtolower(trim((string) ($effect['value'] ?? '')));
        } elseif ($action === 'replace_label') {
            $value = strtolower(trim((string) ($effect['to'] ?? '')));
        } else {
            return false;
        }

        if ($value !== strtolower($expectedLabel)) {
            return false;
        }

        $requiredHetCodes = $this->extractRequirementCodes($requirements, 'het', $nameToCodeMap);
        foreach ($requiredCodes as $code) {
            if (!in_array(strtolower($code), $requiredHetCodes, true)) {
                return false;
            }
        }

        return true;
    }

    private function extractRequirementCodes(array $requirements, string $zygosity, array $nameToCodeMap): array
    {
        $codes = [];

        foreach ($requirements as $requirement) {
            $reqZygosity = strtolower(trim((string) ($requirement['zygosity'] ?? '')));
            if ($reqZygosity !== $zygosity) {
                continue;
            }

            $reqCode = strtolower(trim((string) ($requirement['gene_code'] ?? '')));
            if ($reqCode === '') {
                continue;
            }

            $codes[] = $nameToCodeMap[$reqCode] ?? $reqCode;
        }

        return array_values(array_unique($codes));
    }

    private function applyUltramelRules(array $grouped, array $dictionaryByCode, array $context): array
    {
        $grouped = $this->applyAllelicPairRules(
            $grouped,
            $dictionaryByCode,
            $context,
            [
                'left_code' => 'am',
                'right_code' => 'ui',
                'visual_label' => 'Ultramel',
                'combined_code' => 'au',
                'combined_fallback_name' => 'Amel/Ultra',
                'promote_half_when_full' => true,
            ]
        );

        return $this->applyUltramelSixtySixSplit($grouped, $dictionaryByCode);
    }

    private function applyMotleyStripeRules(array $grouped, array $dictionaryByCode, array $context): array
    {
        $grouped = $this->applyAllelicPairRules(
            $grouped,
            $dictionaryByCode,
            $context,
            [
                'left_code' => 'mo',
                'right_code' => 'st',
                'visual_label' => 'Motley/Stripe',
                'combined_code' => 'ms',
                'combined_fallback_name' => 'Motley/Stripe',
                'promote_half_when_full' => false,
            ]
        );

        return $this->applyMotleyStripeSixtySixSplit($grouped, $dictionaryByCode);
    }

    private function applyAllelicPairRules(
        array $grouped,
        array $dictionaryByCode,
        array $context,
        array $config
    ): array {
        if (empty($grouped)) {
            return $grouped;
        }

        $leftCode = $config['left_code'];
        $rightCode = $config['right_code'];
        $visualLabel = $config['visual_label'];
        $combinedCode = $config['combined_code'];
        $combinedFallbackName = $config['combined_fallback_name'];
        $promoteHalfWhenFull = (bool) ($config['promote_half_when_full'] ?? false);
        $pattern = $context['pattern'] ?? 'other';

        $leftName = $this->getGeneNameByCode($dictionaryByCode, $leftCode, $leftCode);
        $rightName = $this->getGeneNameByCode($dictionaryByCode, $rightCode, $rightCode);
        $combinedName = $this->getGeneNameByCode($dictionaryByCode, $combinedCode, $combinedFallbackName);

        $normalized = [];

        foreach ($grouped as $group) {
            $probability = (float) ($group['probability'] ?? 0.0);
            if ($probability <= 0) {
                continue;
            }

            $visualTraits = $group['visual_traits'] ?? [];
            if ($this->arrayContainsLabel($visualTraits, $visualLabel)) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $carrierProbabilities = $group['carrier_probabilities'] ?? [];
            $leftProb = $this->getCarrierProbability($carrierProbabilities, $leftName, $leftCode);
            $rightProb = $this->getCarrierProbability($carrierProbabilities, $rightName, $rightCode);

            if ($leftProb === null || $rightProb === null) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $leftRatio = $leftProb / $probability;
            $rightRatio = $rightProb / $probability;

            $leftIsFull = $this->ratioIs($leftRatio, 1.0);
            $rightIsFull = $this->ratioIs($rightRatio, 1.0);
            $leftIsHalf = $this->ratioIs($leftRatio, 0.5);
            $rightIsHalf = $this->ratioIs($rightRatio, 0.5);

            if ($leftIsFull && $rightIsFull && $pattern === 'hom_vs_hom') {
                $group['visual_traits'] = $this->addVisualLabel($group['visual_traits'] ?? [], $visualLabel);
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            if ($leftIsHalf && $rightIsHalf && $pattern === 'double_het_vs_normal') {
                $group['carrier_probabilities'] = $this->removeCarrierProbabilities(
                    $carrierProbabilities,
                    [$leftName, $leftCode, $rightName, $rightCode]
                );
                $group['carrier_probabilities'] = $this->setCarrierProbability(
                    $group['carrier_probabilities'],
                    $combinedName,
                    $combinedCode,
                    $probability
                );
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            if ($leftIsHalf && $rightIsHalf && $pattern === 'split_hets') {
                $withProb = $probability * 0.25;
                $withoutProb = $probability - $withProb;

                if ($withProb > 0) {
                    $withGroup = $group;
                    $withGroup['probability'] = $withProb;
                    $withGroup['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                        $carrierProbabilities,
                        $withProb / $probability
                    );
                    $withGroup['visual_traits'] = $this->addVisualLabel($withGroup['visual_traits'] ?? [], $visualLabel);
                    $withGroup['carrier_probabilities'] = $this->setCarrierProbability(
                        $withGroup['carrier_probabilities'],
                        $leftName,
                        $leftCode,
                        $withProb
                    );
                    $withGroup['carrier_probabilities'] = $this->setCarrierProbability(
                        $withGroup['carrier_probabilities'],
                        $rightName,
                        $rightCode,
                        $withProb
                    );
                    $normalized = $this->mergeGroupedResult($normalized, $withGroup);
                }

                if ($withoutProb > 0) {
                    $withoutGroup = $group;
                    $withoutGroup['probability'] = $withoutProb;
                    $withoutGroup['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                        $carrierProbabilities,
                        $withoutProb / $probability
                    );
                    $withoutGroup['carrier_probabilities'] = $this->removeCarrierProbabilities(
                        $withoutGroup['carrier_probabilities'],
                        [$leftName, $leftCode, $rightName, $rightCode]
                    );
                    $withoutGroup['carrier_probabilities'] = $this->setCarrierProbability(
                        $withoutGroup['carrier_probabilities'],
                        $combinedName,
                        $combinedCode,
                        $withoutProb * (2 / 3)
                    );
                    $normalized = $this->mergeGroupedResult($normalized, $withoutGroup);
                }

                continue;
            }

            if (($leftIsFull && $rightIsHalf) || ($rightIsFull && $leftIsHalf)) {
                if ($pattern !== 'hom_vs_het' && !$promoteHalfWhenFull) {
                    $normalized = $this->mergeGroupedResult($normalized, $group);
                    continue;
                }

                if ($pattern !== 'hom_vs_het' && $promoteHalfWhenFull) {
                    $group['visual_traits'] = $this->addVisualLabel($group['visual_traits'] ?? [], $visualLabel);
                    $group['carrier_probabilities'] = $this->setCarrierProbability(
                        $carrierProbabilities,
                        $leftName,
                        $leftCode,
                        $probability
                    );
                    $group['carrier_probabilities'] = $this->setCarrierProbability(
                        $group['carrier_probabilities'],
                        $rightName,
                        $rightCode,
                        $probability
                    );
                    $normalized = $this->mergeGroupedResult($normalized, $group);
                    continue;
                }

                $halfRatio = $leftIsHalf ? $leftRatio : $rightRatio;
                $withProb = $probability * $halfRatio;
                $withoutProb = $probability - $withProb;

                if ($withProb > 0) {
                    $withGroup = $group;
                    $withGroup['probability'] = $withProb;
                    $withGroup['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                        $carrierProbabilities,
                        $withProb / $probability
                    );
                    $withGroup['visual_traits'] = $this->addVisualLabel($withGroup['visual_traits'] ?? [], $visualLabel);
                    $withGroup['carrier_probabilities'] = $this->setCarrierProbability(
                        $withGroup['carrier_probabilities'],
                        $leftName,
                        $leftCode,
                        $withProb
                    );
                    $withGroup['carrier_probabilities'] = $this->setCarrierProbability(
                        $withGroup['carrier_probabilities'],
                        $rightName,
                        $rightCode,
                        $withProb
                    );
                    $normalized = $this->mergeGroupedResult($normalized, $withGroup);
                }

                if ($withoutProb > 0) {
                    $withoutGroup = $group;
                    $withoutGroup['probability'] = $withoutProb;
                    $withoutGroup['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                        $carrierProbabilities,
                        $withoutProb / $probability
                    );

                    if ($leftIsFull) {
                        $withoutGroup['carrier_probabilities'] = $this->removeCarrierProbabilities(
                            $withoutGroup['carrier_probabilities'],
                            [$rightName, $rightCode]
                        );
                        $withoutGroup['carrier_probabilities'] = $this->setCarrierProbability(
                            $withoutGroup['carrier_probabilities'],
                            $leftName,
                            $leftCode,
                            $withoutProb
                        );
                    } else {
                        $withoutGroup['carrier_probabilities'] = $this->removeCarrierProbabilities(
                            $withoutGroup['carrier_probabilities'],
                            [$leftName, $leftCode]
                        );
                        $withoutGroup['carrier_probabilities'] = $this->setCarrierProbability(
                            $withoutGroup['carrier_probabilities'],
                            $rightName,
                            $rightCode,
                            $withoutProb
                        );
                    }

                    $normalized = $this->mergeGroupedResult($normalized, $withoutGroup);
                }

                continue;
            }

            $normalized = $this->mergeGroupedResult($normalized, $group);
        }

        return $normalized;
    }

    private function applyMotleyStripeSixtySixSplit(array $grouped, array $dictionaryByCode): array
    {
        if (empty($grouped)) {
            return $grouped;
        }

        $motleyName = $this->getGeneNameByCode($dictionaryByCode, 'mo', 'Motley');
        $stripeName = $this->getGeneNameByCode($dictionaryByCode, 'st', 'Stripe');
        $combinedName = $this->getGeneNameByCode($dictionaryByCode, 'ms', 'Motley/Stripe');
        $normalized = [];

        foreach ($grouped as $group) {
            $probability = (float) ($group['probability'] ?? 0.0);
            if ($probability <= 0) {
                continue;
            }

            if ($this->arrayContainsLabel($group['visual_traits'] ?? [], 'Motley/Stripe')) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $carrierProbabilities = $group['carrier_probabilities'] ?? [];
            $motleyProbability = $this->getCarrierProbability($carrierProbabilities, $motleyName, 'mo');
            $stripeProbability = $this->getCarrierProbability($carrierProbabilities, $stripeName, 'st');

            if ($motleyProbability === null || $stripeProbability === null) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $motleyRatio = $motleyProbability / $probability;
            $stripeRatio = $stripeProbability / $probability;
            $motleyIsSixtySix = $this->ratioIs($motleyRatio, 2 / 3);
            $stripeIsSixtySix = $this->ratioIs($stripeRatio, 2 / 3);

            if ($motleyIsSixtySix === $stripeIsSixtySix) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            if ($motleyRatio <= 0 || $stripeRatio <= 0) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $carrierBranchProbability = $probability * (2 / 3);
            $visualBranchProbability = $probability - $carrierBranchProbability;

            if ($carrierBranchProbability > 0) {
                $carrierBranch = $group;
                $carrierBranch['probability'] = $carrierBranchProbability;
                $carrierBranch['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                    $carrierProbabilities,
                    $carrierBranchProbability / $probability
                );
                $carrierBranch['carrier_probabilities'] = $this->removeCarrierProbabilities(
                    $carrierBranch['carrier_probabilities'],
                    [$motleyName, 'mo', $stripeName, 'st', $combinedName, 'ms']
                );
                $carrierBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $carrierBranch['carrier_probabilities'],
                    $combinedName,
                    'ms',
                    $carrierBranchProbability
                );
                $normalized = $this->mergeGroupedResult($normalized, $carrierBranch);
            }

            if ($visualBranchProbability > 0) {
                $visualBranch = $group;
                $visualBranch['probability'] = $visualBranchProbability;
                $visualBranch['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                    $carrierProbabilities,
                    $visualBranchProbability / $probability
                );
                $visualBranch['visual_traits'] = $this->addVisualLabel(
                    $visualBranch['visual_traits'] ?? [],
                    'Motley/Stripe'
                );
                $visualBranch['carrier_probabilities'] = $this->removeCarrierProbabilities(
                    $visualBranch['carrier_probabilities'],
                    [$combinedName, 'ms']
                );
                $visualBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $visualBranch['carrier_probabilities'],
                    $motleyName,
                    'mo',
                    $visualBranchProbability
                );
                $visualBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $visualBranch['carrier_probabilities'],
                    $stripeName,
                    'st',
                    $visualBranchProbability
                );
                $normalized = $this->mergeGroupedResult($normalized, $visualBranch);
            }
        }

        return $normalized;
    }

    private function applyUltramelSixtySixSplit(array $grouped, array $dictionaryByCode): array
    {
        if (empty($grouped)) {
            return $grouped;
        }

        $amelName = $this->getGeneNameByCode($dictionaryByCode, 'am', 'Amel');
        $ultraName = $this->getGeneNameByCode($dictionaryByCode, 'ui', 'Ultra');
        $combinedName = $this->getGeneNameByCode($dictionaryByCode, 'au', 'Amel/Ultra');
        $normalized = [];

        foreach ($grouped as $group) {
            $probability = (float) ($group['probability'] ?? 0.0);
            if ($probability <= 0) {
                continue;
            }

            if ($this->arrayContainsLabel($group['visual_traits'] ?? [], 'Ultramel')) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $carrierProbabilities = $group['carrier_probabilities'] ?? [];
            $amelProbability = $this->getCarrierProbability($carrierProbabilities, $amelName, 'am');
            $ultraProbability = $this->getCarrierProbability($carrierProbabilities, $ultraName, 'ui');

            if ($amelProbability === null || $ultraProbability === null) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $amelRatio = $amelProbability / $probability;
            $ultraRatio = $ultraProbability / $probability;
            $amelIsSixtySix = $this->ratioIs($amelRatio, 2 / 3);
            $ultraIsSixtySix = $this->ratioIs($ultraRatio, 2 / 3);

            if ($amelIsSixtySix === $ultraIsSixtySix) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            if ($amelRatio <= 0 || $ultraRatio <= 0) {
                $normalized = $this->mergeGroupedResult($normalized, $group);
                continue;
            }

            $carrierBranchProbability = $probability * (2 / 3);
            $visualBranchProbability = $probability - $carrierBranchProbability;

            if ($carrierBranchProbability > 0) {
                $carrierBranch = $group;
                $carrierBranch['probability'] = $carrierBranchProbability;
                $carrierBranch['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                    $carrierProbabilities,
                    $carrierBranchProbability / $probability
                );
                $carrierBranch['carrier_probabilities'] = $this->removeCarrierProbabilities(
                    $carrierBranch['carrier_probabilities'],
                    [$amelName, 'am', $ultraName, 'ui', $combinedName, 'au']
                );
                $carrierBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $carrierBranch['carrier_probabilities'],
                    $combinedName,
                    'au',
                    $carrierBranchProbability
                );
                $normalized = $this->mergeGroupedResult($normalized, $carrierBranch);
            }

            if ($visualBranchProbability > 0) {
                $visualBranch = $group;
                $visualBranch['probability'] = $visualBranchProbability;
                $visualBranch['carrier_probabilities'] = $this->scaleCarrierProbabilities(
                    $carrierProbabilities,
                    $visualBranchProbability / $probability
                );
                $visualBranch['visual_traits'] = $this->addVisualLabel(
                    $visualBranch['visual_traits'] ?? [],
                    'Ultramel'
                );
                $visualBranch['carrier_probabilities'] = $this->removeCarrierProbabilities(
                    $visualBranch['carrier_probabilities'],
                    [$combinedName, 'au']
                );
                $visualBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $visualBranch['carrier_probabilities'],
                    $amelName,
                    'am',
                    $visualBranchProbability
                );
                $visualBranch['carrier_probabilities'] = $this->setCarrierProbability(
                    $visualBranch['carrier_probabilities'],
                    $ultraName,
                    'ui',
                    $visualBranchProbability
                );
                $normalized = $this->mergeGroupedResult($normalized, $visualBranch);
            }
        }

        return $normalized;
    }

    private function mergeGroupedResult(array $normalized, array $group): array
    {
        $visualTraits = $this->sortTraits($group['visual_traits'] ?? []);
        $key = implode('||', $visualTraits);

        if (!isset($normalized[$key])) {
            $normalized[$key] = [
                'visual_traits' => $visualTraits,
                'carrier_probabilities' => [],
                'probability' => 0.0,
                'homo_count' => 0,
            ];
        }

        $normalized[$key]['probability'] += (float) ($group['probability'] ?? 0.0);
        $normalized[$key]['homo_count'] = max($normalized[$key]['homo_count'], (int) ($group['homo_count'] ?? 0));

        foreach (($group['carrier_probabilities'] ?? []) as $name => $probability) {
            $normalized[$key]['carrier_probabilities'][$name] = ($normalized[$key]['carrier_probabilities'][$name] ?? 0)
                + $probability;
        }

        return $normalized;
    }

    private function getAmelUltraNames(array $dictionaryByCode): array
    {
        $amelName = $dictionaryByCode['am']['name'] ?? 'amel';
        $ultraName = $dictionaryByCode['ui']['name'] ?? 'ultra';

        return [(string) $amelName, (string) $ultraName];
    }

    private function getAmelUltraCombinedName(array $dictionaryByCode): string
    {
        $combined = $dictionaryByCode['au']['name'] ?? null;
        if ($combined !== null) {
            $combined = trim((string) $combined);
            if ($combined !== '') {
                return $combined;
            }
        }

        return 'Amel/Ultra';
    }

    private function getCarrierProbability(array $carrierProbabilities, string $geneName, string $geneCode): ?float
    {
        $geneKey = strtolower(trim($geneName));
        $codeKey = strtolower(trim($geneCode));

        foreach ($carrierProbabilities as $name => $probability) {
            $key = strtolower(trim((string) $name));
            if ($key === $geneKey || $key === $codeKey) {
                return (float) $probability;
            }
        }

        return null;
    }

    private function ratioIs(float $ratio, float $target): bool
    {
        return abs($ratio - $target) < 0.005;
    }

    private function getGeneNameByCode(array $dictionaryByCode, string $geneCode, string $fallback): string
    {
        $key = strtolower(trim($geneCode));
        if ($key !== '' && isset($dictionaryByCode[$key]['name'])) {
            $name = trim((string) $dictionaryByCode[$key]['name']);
            if ($name !== '') {
                return $name;
            }
        }

        $fallback = trim($fallback);

        return $fallback === '' ? $geneCode : $fallback;
    }

    private function addVisualLabel(array $visualTraits, string $label): array
    {
        $label = trim($label);
        if ($label === '' || $this->arrayContainsLabel($visualTraits, $label)) {
            return $visualTraits;
        }

        $visualTraits[] = $label;

        return $visualTraits;
    }

    private function addUltramelLabel(array $visualTraits): array
    {
        return $this->addVisualLabel($visualTraits, 'Ultramel');
    }

    private function scaleCarrierProbabilities(array $carrierProbabilities, float $scale): array
    {
        if ($scale === 1.0) {
            return $carrierProbabilities;
        }

        $scaled = [];
        foreach ($carrierProbabilities as $name => $probability) {
            $scaled[$name] = $probability * $scale;
        }

        return $scaled;
    }

    private function setCarrierProbability(
        array $carrierProbabilities,
        string $geneName,
        string $geneCode,
        float $probability
    ): array {
        $geneKey = strtolower(trim($geneName));
        $codeKey = strtolower(trim($geneCode));
        $updated = false;

        foreach ($carrierProbabilities as $name => $value) {
            $key = strtolower(trim((string) $name));
            if ($key === $geneKey || $key === $codeKey) {
                $carrierProbabilities[$name] = $probability;
                $updated = true;
            }
        }

        if (!$updated && $geneKey !== '') {
            $carrierProbabilities[$geneName] = $probability;
        }

        return $carrierProbabilities;
    }

    private function removeCarrierProbabilities(array $carrierProbabilities, array $needles): array
    {
        $needleKeys = array_filter(array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $needles), fn ($value) => $value !== '');

        if (empty($needleKeys)) {
            return $carrierProbabilities;
        }

        return array_filter($carrierProbabilities, function ($probability, $name) use ($needleKeys) {
            $key = strtolower(trim((string) $name));
            if ($key === '') {
                return true;
            }

            return !in_array($key, $needleKeys, true);
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function applyCombinedCarrierCleanup(
        array $carrierProbabilities,
        float $groupProbability,
        array $dictionaryByCode
    ): array {
        if ($groupProbability <= 0 || empty($carrierProbabilities)) {
            return $carrierProbabilities;
        }

        $carrierProbabilities = $this->applyCombinedCarrierCleanupForPair(
            $carrierProbabilities,
            $groupProbability,
            $dictionaryByCode,
            'am',
            'ui',
            'au'
        );
        $carrierProbabilities = $this->applyCombinedCarrierCleanupForPair(
            $carrierProbabilities,
            $groupProbability,
            $dictionaryByCode,
            'mo',
            'st',
            'ms'
        );

        return $carrierProbabilities;
    }

    private function applyCombinedCarrierCleanupForPair(
        array $carrierProbabilities,
        float $groupProbability,
        array $dictionaryByCode,
        string $leftCode,
        string $rightCode,
        string $combinedCode
    ): array {
        $leftName = $this->getGeneNameByCode($dictionaryByCode, $leftCode, $leftCode);
        $rightName = $this->getGeneNameByCode($dictionaryByCode, $rightCode, $rightCode);
        $combinedName = $this->getGeneNameByCode($dictionaryByCode, $combinedCode, "{$leftName}/{$rightName}");

        $combinedProbability = $this->getCarrierProbability($carrierProbabilities, $combinedName, $combinedCode);
        if ($combinedProbability === null || !$this->ratioIs($combinedProbability / $groupProbability, 0.5)) {
            return $carrierProbabilities;
        }

        $leftProbability = $this->getCarrierProbability($carrierProbabilities, $leftName, $leftCode);
        $rightProbability = $this->getCarrierProbability($carrierProbabilities, $rightName, $rightCode);

        if (
            $leftProbability === null
            || $rightProbability === null
            || !$this->ratioIs($leftProbability / $groupProbability, 0.5)
            || !$this->ratioIs($rightProbability / $groupProbability, 0.5)
        ) {
            return $carrierProbabilities;
        }

        return $this->removeCarrierProbabilities(
            $carrierProbabilities,
            [$leftName, $leftCode, $rightName, $rightCode]
        );
    }

    private function buildGroupState(array $visualTraits, array $carrierProbabilities, array $nameToCodeMap): array
    {
        $state = [];

        foreach ($visualTraits as $trait) {
            $nameKey = strtolower((string) $trait);
            if ($nameKey === '') {
                continue;
            }

            $state[] = [
                'gene_code' => $nameToCodeMap[$nameKey] ?? $nameKey,
                'gene_name' => (string) $trait,
                'zygosity' => 'hom',
            ];
        }

        foreach ($carrierProbabilities as $name => $probability) {
            $nameKey = strtolower((string) $name);
            if ($nameKey === '') {
                continue;
            }

            $state[] = [
                'gene_code' => $nameToCodeMap[$nameKey] ?? $nameKey,
                'gene_name' => (string) $name,
                'zygosity' => 'het',
            ];
        }

        return $state;
    }

    private function matchesException(array $state, array $requirements): bool
    {
        if (empty($requirements)) {
            return false;
        }

        foreach ($requirements as $requirement) {
            $reqCode = strtolower(trim((string) ($requirement['gene_code'] ?? '')));
            $reqZygosity = strtolower(trim((string) ($requirement['zygosity'] ?? '')));

            if ($reqCode === '' || $reqZygosity === '') {
                return false;
            }

            $normalizedReqCode = $this->exceptionNameToCode[$reqCode] ?? $reqCode;

            $matched = false;
            foreach ($state as $entry) {
                $entryCode = strtolower((string) ($entry['gene_code'] ?? ''));
                $entryName = strtolower((string) ($entry['gene_name'] ?? ''));
                $entryZygosity = strtolower((string) ($entry['zygosity'] ?? ''));

                if ($entryZygosity !== $reqZygosity) {
                    continue;
                }

                if ($normalizedReqCode === $entryCode || $reqCode === $entryName) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function applyExceptionEffectToTraits(array $traits, array $effect): array
    {
        $action = strtolower(trim((string) ($effect['action'] ?? '')));
        $target = strtolower(trim((string) ($effect['target'] ?? '')));

        if ($target !== 'homozygote_label') {
            return $traits;
        }

        if ($action === 'add_label') {
            $value = trim((string) ($effect['value'] ?? ''));
            if ($value === '') {
                return $traits;
            }

            if (!in_array($value, $traits, true)) {
                array_unshift($traits, $value);
            }

            return $traits;
        }

        if ($action === 'replace_label') {
            $to = trim((string) ($effect['to'] ?? ''));
            if ($to === '') {
                return $traits;
            }

            $from = $effect['from'] ?? null;
            if (is_array($from) && !empty($from)) {
                foreach ($traits as $index => $label) {
                    foreach ($from as $candidate) {
                        if (strcasecmp(trim((string) $candidate), trim($label)) === 0) {
                            $traits[$index] = $to;
                        }
                    }
                }

                return $this->uniqueListPreserveOrder($traits);
            }

            return [$to];
        }

        if ($action === 'set_label') {
            $value = trim((string) ($effect['value'] ?? ''));
            return $value === '' ? $traits : [$value];
        }

        return $traits;
    }

    private function formatCarrierLabel(string $geneName, float $percentage): string
    {
        if ($percentage >= 99.99) {
            return "Het {$geneName}";
        }

        if (abs($percentage - 66.6667) < 0.5) {
            return "66% Het {$geneName}";
        }

        if (abs($percentage - 50.0) < 0.5) {
            return "50% Het {$geneName}";
        }

        $formatted = rtrim(rtrim(number_format($percentage, 2, '.', ''), '0'), '.');

        return "{$formatted}% Het {$geneName}";
    }

    private function prefixSuperLabel(string $geneName): string
    {
        $trimmed = trim($geneName);
        if ($trimmed === '') {
            return $geneName;
        }

        return stripos($trimmed, 'super ') === 0 ? $geneName : "Super {$trimmed}";
    }

    private function uniqueListPreserveOrder(array $values): array
    {
        $unique = [];
        $seen = [];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $key = strtolower($value);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $value;
        }

        return $unique;
    }

    private function normalizeGeneType(?string $geneType): string
    {
        $geneType = strtolower(trim((string) $geneType));

        if ($geneType === '') {
            throw new \InvalidArgumentException('Gene type is required.');
        }

        return $geneType;
    }

    private function getGeneTypeHandler(?string $geneType): GeneTypeHandler
    {
        $geneType = $this->normalizeGeneType($geneType);

        if (!isset($this->geneTypeHandlers[$geneType])) {
            throw new \InvalidArgumentException("Unsupported gene type: {$geneType}");
        }

        return $this->geneTypeHandlers[$geneType];
    }
}
