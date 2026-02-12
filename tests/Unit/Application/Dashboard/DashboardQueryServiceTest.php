<?php

namespace Tests\Unit\Application\Dashboard;

use App\Application\Dashboard\Queries\DashboardQueryService;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DashboardQueryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calculates_days_to_feed_for_today_past_and_future_dates(): void
    {
        Carbon::setTestNow('2026-02-12 15:45:00');

        $service = new DashboardQueryService(
            new LitterTimelineCalculator(),
            new AnimalWinteringCycleResolver()
        );
        $method = new ReflectionMethod($service, 'calculateFeedingMetrics');
        $method->setAccessible(true);

        $today = $method->invoke($service, Carbon::parse('2026-02-05 08:00:00'), 7);
        $this->assertSame('2026-02-12', $today['next_feed_date']);
        $this->assertSame(0, $today['days_to_feed']);
        $this->assertSame('0', $today['days_to_feed_label']);

        $future = $method->invoke($service, Carbon::parse('2026-02-10 08:00:00'), 4);
        $this->assertSame('2026-02-14', $future['next_feed_date']);
        $this->assertSame(2, $future['days_to_feed']);
        $this->assertSame('+2', $future['days_to_feed_label']);

        $past = $method->invoke($service, Carbon::parse('2026-02-01 08:00:00'), 7);
        $this->assertSame('2026-02-08', $past['next_feed_date']);
        $this->assertSame(-4, $past['days_to_feed']);
        $this->assertSame('-4', $past['days_to_feed_label']);
    }
}
