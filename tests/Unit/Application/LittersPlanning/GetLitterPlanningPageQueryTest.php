<?php

namespace Tests\Unit\Application\LittersPlanning;

use App\Application\LittersPlanning\Queries\GetLitterPlanningPageQuery;
use App\Application\LittersPlanning\Repositories\PossibleConnectionsRepository;
use App\Services\Genetics\GenotypeCalculator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GetLitterPlanningPageQueryTest extends TestCase
{
    public function test_season_offspring_summary_numeric_count_uses_average_eggs_and_percentage_sum(): void
    {
        $query = new GetLitterPlanningPageQuery(
            $this->createMock(GenotypeCalculator::class),
            $this->createMock(PossibleConnectionsRepository::class),
        );

        $method = new ReflectionMethod($query, 'buildSeasonOffspringSummaryRows');
        $method->setAccessible(true);

        $rows = [
            [
                'litter_id' => 10,
                'litter_code' => 'L10',
                'season' => 2026,
                'traits_name' => 'Amel',
                'visual_traits' => ['Amel'],
                'carrier_traits' => [],
                'traits_count' => 1,
                'percentage' => 100.0,
                'percentage_label' => '100,00%',
                'litter_url' => '#',
                'litter_eggs_to_incubation' => 10,
                'connection_date' => '2026-01-10',
                'has_connection_date' => true,
            ],
            [
                'litter_id' => 11,
                'litter_code' => 'L11',
                'season' => 2026,
                'traits_name' => 'Amel',
                'visual_traits' => ['Amel'],
                'carrier_traits' => [],
                'traits_count' => 1,
                'percentage' => 50.0,
                'percentage_label' => '50,00%',
                'litter_url' => '#',
                'litter_eggs_to_incubation' => 20,
                'connection_date' => '2026-01-12',
                'has_connection_date' => true,
            ],
        ];

        $result = $method->invoke($query, $rows, 10.0, 'percentage_sum', 'desc');

        $this->assertCount(1, $result);
        $this->assertSame('Amel', $result[0]['morph_name']);
        $this->assertSame(150.0, $result[0]['percentage_sum']);
        $this->assertSame(10.0, $result[0]['avg_eggs_to_incubation']);
        $this->assertSame('10', $result[0]['avg_eggs_to_incubation_label']);
        $this->assertSame(15.0, $result[0]['numeric_count']);
        $this->assertSame('15', $result[0]['numeric_count_label']);
    }
}
