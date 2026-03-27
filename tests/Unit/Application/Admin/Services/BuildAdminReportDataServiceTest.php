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
                        'animal_id' => 11,
                        'animal_name' => 'Ghost',
                        'public_tag' => null,
                        'feedings' => [],
                        'weights' => [],
                        'molts' => [
                            ['label' => 'Wpis dodany 11:20'],
                        ],
                    ],
                ]);
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
        $this->assertSame(1, $report['meta']['molts_count']);
        $this->assertCount(2, $report['rows']);

        CarbonImmutable::setTestNow();
    }
}
