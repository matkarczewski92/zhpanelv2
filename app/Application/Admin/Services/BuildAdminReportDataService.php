<?php

namespace App\Application\Admin\Services;

use App\Domain\Admin\Reports\AdminReportSourceRepositoryInterface;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class BuildAdminReportDataService
{
    public const TYPE_SALES = 'sales';
    public const TYPE_DAILY_ENTERED_DATA = 'daily_entered_data';
    public const TYPE_QR_SCANNER_SESSION = 'qr_scanner_session';

    public function __construct(
        private readonly AdminReportSourceRepositoryInterface $sourceRepository
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function handle(array $filters): array
    {
        $type = (string) ($filters['report_type'] ?? '');

        return match ($type) {
            self::TYPE_SALES => $this->buildSalesReport($filters),
            self::TYPE_DAILY_ENTERED_DATA => $this->buildDailyEnteredDataReport($filters),
            self::TYPE_QR_SCANNER_SESSION => $this->buildQrScannerSessionReport($filters),
            default => throw new InvalidArgumentException('Nieobslugiwany typ raportu.'),
        };
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildSalesReport(array $filters): array
    {
        $from = CarbonImmutable::parse((string) $filters['date_from'])->startOfDay();
        $to = CarbonImmutable::parse((string) $filters['date_to'])->endOfDay();
        $rows = $this->sourceRepository->getSalesRows($from, $to);
        $total = $rows->sum(fn (array $row): float => (float) ($row['sale_price'] ?? 0));

        return [
            'type' => self::TYPE_SALES,
            'title' => 'Raport sprzedazy',
            'report_name' => 'Raport sprzedazy',
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
            'meta' => [
                'generated_at' => now()->format('Y-m-d H:i'),
                'range_label' => $from->toDateString() . ' - ' . $to->toDateString(),
                'item_count' => $rows->count(),
                'total_amount' => $total,
                'total_amount_label' => number_format($total, 2, ',', ' ') . ' zl',
            ],
            'rows' => $rows->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildDailyEnteredDataReport(array $filters): array
    {
        $day = CarbonImmutable::parse((string) $filters['report_date'])->startOfDay();
        $rows = $this->sourceRepository->getDailyEnteredDataRows($day);

        return [
            'type' => self::TYPE_DAILY_ENTERED_DATA,
            'title' => 'Raport wprowadzonych danych',
            'report_name' => 'Raport wprowadzonych danych',
            'filters' => [
                'report_date' => $day->toDateString(),
            ],
            'meta' => [
                'generated_at' => now()->format('Y-m-d H:i'),
                'report_date_label' => $day->toDateString(),
                'item_count' => $rows->count(),
                'feedings_count' => $rows->sum(fn (array $row): int => count($row['feedings'] ?? [])),
                'weights_count' => $rows->sum(fn (array $row): int => count($row['weights'] ?? [])),
                'molts_count' => $rows->sum(fn (array $row): int => count($row['molts'] ?? [])),
            ],
            'rows' => $rows->all(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildQrScannerSessionReport(array $filters): array
    {
        $startedAt = CarbonImmutable::parse((string) ($filters['session_started_at'] ?? now()->toIso8601String()));
        $entries = collect($filters['session_entries'] ?? [])
            ->filter(fn (mixed $entry): bool => is_array($entry) && isset($entry['animal_id'], $entry['mode']))
            ->map(function (array $entry): array {
                return [
                    'animal_id' => (int) ($entry['animal_id'] ?? 0),
                    'mode' => (string) ($entry['mode'] ?? ''),
                    'occurred_at' => isset($entry['occurred_at']) ? CarbonImmutable::parse((string) $entry['occurred_at']) : null,
                    'feed_type' => trim((string) ($entry['feed_type'] ?? '')),
                    'quantity' => (int) ($entry['quantity'] ?? 0),
                    'value' => isset($entry['value']) ? (float) $entry['value'] : null,
                ];
            })
            ->filter(fn (array $entry): bool => $entry['animal_id'] > 0 && in_array($entry['mode'], ['feeding', 'weight', 'molt'], true))
            ->values();

        $animals = $this->sourceRepository
            ->getAnimalSnapshotsByIds($entries->pluck('animal_id')->unique()->values()->all())
            ->keyBy('animal_id');

        $rows = $entries
            ->groupBy('animal_id')
            ->map(function ($animalEntries, $animalId) use ($animals): ?array {
                $animal = $animals->get((int) $animalId);

                if (!is_array($animal)) {
                    return null;
                }

                return [
                    'animal_id' => $animal['animal_id'],
                    'animal_name' => $animal['animal_name'],
                    'public_tag' => $animal['public_tag'],
                    'feedings' => $animalEntries
                        ->where('mode', 'feeding')
                        ->map(fn (array $entry): array => [
                            'label' => trim(($entry['feed_type'] !== '' ? $entry['feed_type'] : '-') . ' x' . max(1, (int) $entry['quantity'])),
                        ])
                        ->values()
                        ->all(),
                    'weights' => $animalEntries
                        ->where('mode', 'weight')
                        ->map(fn (array $entry): array => [
                            'label' => $this->formatWeightLabel($entry['value'], $entry['occurred_at']),
                        ])
                        ->values()
                        ->all(),
                    'molts' => $animalEntries
                        ->where('mode', 'molt')
                        ->map(fn (array $entry): array => [
                            'label' => $this->formatMoltLabel($entry['occurred_at']),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->sortBy('animal_id')
            ->values();

        $endedAt = $entries
            ->pluck('occurred_at')
            ->filter(fn (mixed $entry): bool => $entry instanceof CarbonImmutable)
            ->sort()
            ->last() ?? $startedAt;

        return [
            'type' => self::TYPE_QR_SCANNER_SESSION,
            'title' => 'Raport sesji skanera QR',
            'report_name' => 'Raport sesji skanera QR',
            'filters' => [
                'session_started_at' => $startedAt->toIso8601String(),
                'session_ended_at' => $endedAt->toIso8601String(),
            ],
            'meta' => [
                'generated_at' => now()->format('Y-m-d H:i'),
                'session_started_at_label' => $startedAt->format('Y-m-d H:i'),
                'session_ended_at_label' => $endedAt->format('Y-m-d H:i'),
                'session_label' => $startedAt->format('Y-m-d H:i') . ' - ' . $endedAt->format('Y-m-d H:i'),
                'item_count' => $entries->count(),
                'animal_count' => $rows->count(),
                'feedings_count' => $entries->where('mode', 'feeding')->count(),
                'weights_count' => $entries->where('mode', 'weight')->count(),
                'molts_count' => $entries->where('mode', 'molt')->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    private function formatWeightLabel(?float $value, ?CarbonImmutable $occurredAt): string
    {
        $formattedValue = $value === null
            ? '-'
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        if (!$occurredAt) {
            return $formattedValue . ' g';
        }

        return $formattedValue . ' g ' . $occurredAt->format('H:i');
    }

    private function formatMoltLabel(?CarbonImmutable $occurredAt): string
    {
        if (!$occurredAt) {
            return 'Wpis dodany';
        }

        return 'Wpis dodany ' . $occurredAt->format('H:i');
    }
}
