<?php

namespace Tests\Unit\Application\Litters;

use App\Application\Litters\Queries\GetLitterShowQuery;
use App\Application\Litters\Support\IncubationTimelineBuilder;
use App\Application\Litters\Support\LitterStatusResolver;
use App\Application\Litters\Support\LitterTimelineCalculator;
use App\Models\Litter;
use App\Services\Genetics\GenotypeCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GetLitterShowFeedingCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_offspring_feeding_count_excludes_feed_ids_nine_and_ten(): void
    {
        DB::table('animals')->insert([
            'id' => 101,
            'name' => 'L.1.1',
            'sex' => 1,
            'date_of_birth' => '2026-07-20',
            'litter_id' => 1,
        ]);

        DB::table('animal_feedings')->insert([
            ['animal_id' => 101, 'feed_id' => 8, 'amount' => 1],
            ['animal_id' => 101, 'feed_id' => 9, 'amount' => 1],
            ['animal_id' => 101, 'feed_id' => 10, 'amount' => 1],
            ['animal_id' => 101, 'feed_id' => 11, 'amount' => 1],
        ]);

        $litter = new Litter([
            'category' => 1,
            'litter_code' => 'L.1',
            'season' => 2026,
        ]);
        $litter->id = 1;
        $litter->exists = true;
        $litter->setRelation('mainPhoto', null);
        $litter->setRelation('gallery', collect());
        $litter->setRelation('maleParent', null);
        $litter->setRelation('femaleParent', null);
        $litter->setRelation('adnotation', null);

        $timelineCalculator = new LitterTimelineCalculator();
        $query = new GetLitterShowQuery(
            new LitterStatusResolver(),
            $timelineCalculator,
            new IncubationTimelineBuilder($timelineCalculator),
            $this->createMock(GenotypeCalculator::class),
        );

        $page = $query->handle($litter);

        $this->assertSame(2, $page->offspring[0]['feedings_count']);
    }

    private function prepareSchema(): void
    {
        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('sex');
            $table->date('date_of_birth')->nullable();
            $table->unsignedBigInteger('litter_id')->nullable();
            $table->unsignedBigInteger('animal_category_id')->nullable();
        });

        Schema::create('animal_feedings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('feed_id');
            $table->integer('amount')->default(1);
        });

        Schema::create('animal_weights', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->decimal('value', 8, 2);
        });

        Schema::create('animal_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->decimal('price', 10, 2)->nullable();
            $table->date('sold_date')->nullable();
        });

        Schema::create('animal_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('value')->nullable();
        });
    }
}
