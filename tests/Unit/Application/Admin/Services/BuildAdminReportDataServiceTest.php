<?php

namespace Tests\Unit\Application\Admin\Services;

use App\Application\Admin\Services\BuildAdminReportDataService;
use App\Domain\Admin\Reports\AdminReportSourceRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BuildAdminReportDataServiceTest extends TestCase
{
    public function test_builds_sales_report_with_total_sum(): void
    {
        $service = new BuildAdminReportDataService(new class implements AdminReportSourceRepositoryInterface {
            public function getSalesRows(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): Collection
            {
                return collect([
                    [
                        'animal_id' => 10,
                        'animal_name' => 'Butter Stripe',
                        'public_tag' => 'ebe5',
                        'sale_date' => '2026-03-03',
                        'sale_price' => 1200.50,
                        'sale_price_label' => '1 200,50 zl',
                    ],
                    [
                        'animal_id' => 11,
                        'animal_name' => 'Ghost',
                        'public_tag' => null,
                        'sale_date' => '2026-03-05',
                        'sale_price' => 899.99,
                        'sale_price_label' => '899,99 zl',
                    ],
                ]);
            }

            public function getDailyEnteredDataRows(\Carbon\CarbonInterface $day): Collection
            {
                return collect();
            }

            public function getAnimalSnapshotsByIds(array $animalIds): Collection
            {
                return collect();
            }
        });

        $report = $service->handle([
            'report_type' => BuildAdminReportDataService::TYPE_SALES,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]);

        $this->assertSame('sales', $report['type']);
        $this->assertSame(2, $report['meta']['item_count']);
        $this->assertSame(2100.49, $report['meta']['total_amount']);
        $this->assertCount(2, $report['rows']);
    }

    public function test_builds_daily_entered_data_report_with_entry_counts(): void
    {
        CarbonImmutable::setTestNow('2026-03-27 14:30:00');

        $service = new BuildAdminReportDataService(new class implements AdminReportSourceRepositoryInterface {
            public function getSalesRows(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): Collection
            {
                return collect();
            }

            public function getDailyEnteredDataRows(\Carbon\CarbonInterface $day): Collection
            {
                return collect([
                    [
                        'animal_id' => 10,
                        'animal_name' => 'Butter Stripe',
                        'public_tag' => 'ebe5',
                        'animal_category_id' => 1,
                        'animal_category_name' => 'W hodowli',
                        'animal_type_id' => 1,
                        'animal_type_name' => 'Waz zbozowy',
                        'feedings' => [
                            ['label' => 'Mouse x1 08:10'],
                            ['label' => 'Mouse x1 12:45'],
                        ],
                        'weights' => [
                            ['label' => '450 g 09:00'],
                        ],
                        'molts' => [],
                    ],
                    [
                        'animal_id' => 12,
                        'animal_name' => 'Amber',
                        'public_tag' => 'abc1',
                        'animal_category_id' => 1,
                        'animal_category_name' => 'W hodowli',
                        'animal_type_id' => 3,
                        'animal_type_name' => 'Pyton zielony',
                        'feedings' => [],
                        'weights' => [],
                        'molts' => [],
                    ],
                    [
                        'animal_id' => 11,
                        'animal_name' => 'Ghost',
                        'public_tag' => null,
                        'animal_category_id' => 5,
                        'animal_category_name' => 'Usuniete',
                        'animal_type_id' => 2,
                        'animal_type_name' => 'Pyton',
                        'feedings' => [],
                        'weights' => [],
                        'molts' => [
                            ['label' => 'Wpis dodany 11:20'],
                        ],
                    ],
                ]);
            }

            public function getAnimalSnapshotsByIds(array $animalIds): Collection
            {
                return collect();
            }
        });

        $report = $service->handle([
            'report_type' => BuildAdminReportDataService::TYPE_DAILY_ENTERED_DATA,
            'report_date' => '2026-03-27',
        ]);

        $this->assertSame('daily_entered_data', $report['type']);
        $this->assertSame(2, $report['meta']['item_count']);
        $this->assertSame(2, $report['meta']['feedings_count']);
        $this->assertSame(1, $report['meta']['weights_count']);
        $this->assertSame(0, $report['meta']['molts_count']);
        $this->assertCount(2, $report['rows']);
        $this->assertCount(1, $report['groups']);
        $this->assertSame('Kategoria: W hodowli', $report['groups'][0]['label']);
        $this->assertCount(2, $report['groups'][0]['types']);
        $this->assertSame(1, $report['groups'][0]['types'][0]['animal_type_id']);
        $this->assertSame('Waz zbozowy', $report['groups'][0]['types'][0]['label']);
        $this->assertSame(3, $report['groups'][0]['types'][1]['animal_type_id']);

        CarbonImmutable::setTestNow();
    }

    public function test_builds_qr_scanner_session_report(): void
    {
        $service = new BuildAdminReportDataService(new class implements AdminReportSourceRepositoryInterface {
            public function getSalesRows(\Carbon\CarbonInterface $from, \Carbon\CarbonInterface $to): Collection
            {
                return collect();
            }

            public function getDailyEnteredDataRows(\Carbon\CarbonInterface $day): Collection
            {
                return collect();
            }

            public function getAnimalSnapshotsByIds(array $animalIds): Collection
            {
                return collect([
                    [
                        'animal_id' => 10,
                        'animal_name' => '<b>Butter</b> Stripe',
                        'public_tag' => 'ebe5',
                    ],
                    [
                        'animal_id' => 11,
                        'animal_name' => 'Ghost',
                        'public_tag' => null,
                    ],
                ]);
            }
        });

        $report = $service->handle([
            'report_type' => BuildAdminReportDataService::TYPE_QR_SCANNER_SESSION,
            'session_started_at' => '2026-03-27 15:00:00',
            'session_entries' => [
                [
                    'mode' => 'feeding',
                    'animal_id' => 10,
                    'occurred_at' => '2026-03-27 15:05:00',
                    'feed_type' => 'Mouse',
                    'quantity' => 1,
                ],
                [
                    'mode' => 'weight',
                    'animal_id' => 10,
                    'occurred_at' => '2026-03-27 15:06:00',
                    'value' => 421.5,
                ],
                [
                    'mode' => 'molt',
                    'animal_id' => 11,
                    'occurred_at' => '2026-03-27 15:07:00',
                ],
            ],
        ]);

        $this->assertSame(BuildAdminReportDataService::TYPE_QR_SCANNER_SESSION, $report['type']);
        $this->assertSame(3, $report['meta']['item_count']);
        $this->assertSame(2, $report['meta']['animal_count']);
        $this->assertSame(1, $report['meta']['feedings_count']);
        $this->assertSame(1, $report['meta']['weights_count']);
        $this->assertSame(1, $report['meta']['molts_count']);
        $this->assertCount(2, $report['rows']);
        $this->assertSame('Mouse x1', $report['rows'][0]['feedings'][0]['label']);
    }
}
