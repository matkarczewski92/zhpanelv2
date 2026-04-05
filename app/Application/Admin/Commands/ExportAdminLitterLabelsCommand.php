<?php

namespace App\Application\Admin\Commands;

use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Models\Litter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExportAdminLitterLabelsCommand
{
    public function __construct(
        private readonly LitterTimelineCalculator $timelineCalculator,
    ) {
    }

    /**
     * @param array{litter_ids: array<int, int>} $data
     * @return array{filename:string,content:string,content_type:string}
     */
    public function handle(array $data): array
    {
        $rows = $this->buildRows($data['litter_ids'] ?? []);

        return [
            'filename' => 'etykiety_mioty_admin.csv',
            'content' => $this->exportCsvWin1250($rows, ';'),
            'content_type' => 'text/csv; charset=windows-1250',
        ];
    }

    /**
     * @param array<int, int> $litterIds
     * @return Collection<int, array<string, string>>
     */
    private function buildRows(array $litterIds): Collection
    {
        return Litter::query()
            ->whereIn('id', $litterIds)
            ->orderBy('id')
            ->get()
            ->map(function (Litter $litter): array {
                return [
                    'litter_code' => (string) $litter->litter_code,
                    'litter_id' => (string) $litter->id,
                    'season' => $this->stringValue($litter->season),
                    'connection_date' => $this->formatDate($litter->connection_date ?? $litter->planned_connection_date),
                    'laying_date' => $this->formatDate($litter->laying_date),
                    'planned_hatching_date' => $this->formatDate($this->timelineCalculator->estimatedHatchingDate($litter)),
                    'laying_eggs_total' => $this->stringValue($litter->laying_eggs_total),
                    'laying_eggs_ok' => $this->stringValue($litter->laying_eggs_ok),
                ];
            });
    }

    /**
     * @param Collection<int, array<string, string>> $rows
     */
    private function exportCsvWin1250(Collection $rows, string $delimiter): string
    {
        $headers = [
            'kod_miotu',
            'id_miotu',
            'sezon',
            'data_laczenia',
            'data_zniosu',
            'planowana_data_wyklucia',
            'ilosc_zniesionych_jaj',
            'ilosc_jaj_do_inkubacji',
        ];

        $lines = [implode($delimiter, $headers)];

        foreach ($rows as $row) {
            $lines[] = implode($delimiter, array_map(
                fn (?string $value): string => $this->csvValue($value, $delimiter),
                [
                    $row['litter_code'] ?? '',
                    $row['litter_id'] ?? '',
                    $row['season'] ?? '',
                    $row['connection_date'] ?? '',
                    $row['laying_date'] ?? '',
                    $row['planned_hatching_date'] ?? '',
                    $row['laying_eggs_total'] ?? '',
                    $row['laying_eggs_ok'] ?? '',
                ]
            ));
        }

        return $this->toWin1250(implode("\r\n", $lines));
    }

    private function formatDate(mixed $value): string
    {
        return $value?->format('Y-m-d') ?? '';
    }

    private function stringValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function csvValue(?string $value, string $delimiter): string
    {
        $normalized = (string) $value;

        if (Str::contains($normalized, [$delimiter, '"', "\n", "\r"])) {
            $normalized = '"' . str_replace('"', '""', $normalized) . '"';
        }

        return $normalized;
    }

    private function toWin1250(string $value): string
    {
        $converted = @iconv('UTF-8', 'Windows-1250//TRANSLIT', $value);

        if ($converted === false) {
            $converted = mb_convert_encoding($value, 'Windows-1250', 'UTF-8');
        }

        return $converted;
    }
}
