<?php

namespace Tests\Unit\Application\Litters;

use App\Application\Litters\Queries\GetLitterShowQuery;
use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Services\Genetics\GenotypeCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GetLitterShowQueryTest extends TestCase
{
    public function test_formats_date_with_days_difference(): void
    {
        $query = new GetLitterShowQuery(
            new LitterStatusResolver(),
            new LitterTimelineCalculator(),
            $this->createMock(GenotypeCalculator::class),
        );

        $method = new ReflectionMethod($query, 'formatDateWithDays');
        $method->setAccessible(true);

        $result = $method->invoke(
            $query,
            Carbon::parse('2026-05-10'),
            Carbon::parse('2026-04-01'),
        );

        $this->assertSame('2026-05-10 (39 dni)', $result);
    }

    public function test_formats_date_without_days_when_reference_missing(): void
    {
        $query = new GetLitterShowQuery(
            new LitterStatusResolver(),
            new LitterTimelineCalculator(),
            $this->createMock(GenotypeCalculator::class),
        );

        $method = new ReflectionMethod($query, 'formatDateWithDays');
        $method->setAccessible(true);

        $result = $method->invoke(
            $query,
            Carbon::parse('2026-05-10'),
            null,
        );

        $this->assertSame('2026-05-10', $result);
    }
}
