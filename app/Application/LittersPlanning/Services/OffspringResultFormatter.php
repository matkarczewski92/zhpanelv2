<?php

namespace App\Application\LittersPlanning\Services;

class OffspringResultFormatter
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{
     *     percentage:float,
     *     percentage_label:string,
     *     traits_name:string,
     *     traits_count:int,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>
     * }>
     */
    public function formatSummary(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->mapBaseRow($row))
            ->sortByDesc('percentage')
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{
     *     percentage:float,
     *     percentage_label:string,
     *     morph_name:string,
     *     phenotype:string,
     *     genotype:string,
     *     traits_count:int,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>
     * }>
     */
    public function formatFull(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $base = $this->mapBaseRow($row);
                $phenotype = implode(', ', $base['visual_traits']);
                $genotype = implode(', ', $base['carrier_traits']);

                return [
                    'percentage' => $base['percentage'],
                    'percentage_label' => $base['percentage_label'],
                    'morph_name' => $base['traits_name'] !== '' ? $base['traits_name'] : ($phenotype !== '' ? $phenotype : '-'),
                    'phenotype' => $phenotype !== '' ? $phenotype : '-',
                    'genotype' => $genotype !== '' ? $genotype : '-',
                    'traits_count' => $base['traits_count'],
                    'visual_traits' => $base['visual_traits'],
                    'carrier_traits' => $base['carrier_traits'],
                ];
            })
            ->sortByDesc('percentage')
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     percentage:float,
     *     percentage_label:string,
     *     traits_name:string,
     *     traits_count:int,
     *     visual_traits:array<int, string>,
     *     carrier_traits:array<int, string>
     * }
     */
    private function mapBaseRow(array $row): array
    {
        $percentage = (float) ($row['percentage'] ?? 0);
        $visualTraits = collect((array) ($row['visual_traits'] ?? []))
            ->map(fn (mixed $trait): string => trim((string) $trait))
            ->filter()
            ->values()
            ->all();
        $carrierTraits = collect((array) ($row['carrier_traits'] ?? []))
            ->map(fn (mixed $trait): string => trim((string) $trait))
            ->filter()
            ->values()
            ->all();

        return [
            'percentage' => $percentage,
            'percentage_label' => number_format($percentage, 2, ',', ' ') . '%',
            'traits_name' => trim((string) ($row['traits_name'] ?? '')),
            'traits_count' => (int) ($row['traits_count'] ?? 0),
            'visual_traits' => $visualTraits,
            'carrier_traits' => $carrierTraits,
        ];
    }
}

