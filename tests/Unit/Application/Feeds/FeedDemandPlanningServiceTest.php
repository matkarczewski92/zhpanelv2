<?php

namespace Tests\Unit\Application\Feeds;

use App\Models\Animal;
use App\Models\Feed;
use App\Models\SystemConfig;
use App\Services\Panel\FeedDemandPlanningService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedDemandPlanningServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_uses_feed_quantity_sum_instead_of_animal_count_for_demand(): void
    {
        $feed = Feed::query()->create([
            'name' => 'Mysz 23-29g',
            'feeding_interval' => 7,
            'amount' => 10,
            'last_price' => 3.50,
        ]);

        SystemConfig::query()->create([
            'key' => 'feedLeadTime',
            'value' => '0',
        ]);

        Animal::query()->create([
            'name' => 'Animal A',
            'sex' => 1,
            'date_of_birth' => '2025-01-01',
            'feed_id' => $feed->id,
            'feed_quantity' => 3,
            'animal_category_id' => 1,
        ]);

        Animal::query()->create([
            'name' => 'Animal B',
            'sex' => 1,
            'date_of_birth' => '2025-01-01',
            'feed_id' => $feed->id,
            'feed_quantity' => 1,
            'animal_category_id' => 2,
        ]);

        Animal::query()->create([
            'name' => 'Ignored Animal',
            'sex' => 1,
            'date_of_birth' => '2025-01-01',
            'feed_id' => $feed->id,
            'feed_quantity' => 10,
            'animal_category_id' => 3,
        ]);

        $result = app(FeedDemandPlanningService::class)->recalculate([
            ['feed_id' => $feed->id, 'order_qty' => 2],
        ]);

        $row = $result['rows'][$feed->id];

        $this->assertSame(4, $row['demand_units']);
        $this->assertSame('2', $row['dk_label']);
        $this->assertSame('3', $row['new_dk_label']);
        $this->assertSame('7,00 zł', $row['row_cost_label']);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('system_config');
        Schema::dropIfExists('animals');
        Schema::dropIfExists('feeds');

        Schema::create('feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('feeding_interval')->default(0);
            $table->integer('amount')->default(0);
            $table->double('last_price', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('second_name')->nullable();
            $table->integer('sex');
            $table->date('date_of_birth');
            $table->unsignedBigInteger('animal_type_id')->nullable();
            $table->unsignedBigInteger('litter_id')->nullable();
            $table->unsignedBigInteger('feed_id')->nullable();
            $table->integer('feed_interval')->nullable();
            $table->integer('feed_quantity')->default(1);
            $table->unsignedBigInteger('animal_category_id')->nullable();
            $table->integer('public_profile')->default(0);
            $table->string('public_profile_tag')->nullable();
            $table->string('secret_tag')->nullable();
            $table->integer('web_gallery')->nullable();
            $table->timestamps();
        });

        Schema::create('system_config', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
}
