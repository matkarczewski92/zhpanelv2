<?php

namespace App\Application\Admin\Services;

use App\Domain\Admin\Reports\AdminReportSourceRepositoryInterface;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class BuildAdminReportDataService
{
    public const TYPE_SALES = 'sales';
    public const TYPE_DAILY_ENTERED_DATA = 'daily_entered_data';

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
}
