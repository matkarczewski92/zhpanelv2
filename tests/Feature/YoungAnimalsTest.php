<?php

namespace Tests\Feature;

use App\Application\Animals\Queries\GetYoungAnimalsIndexQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YoungAnimalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->withoutVite();
        Schema::create('animal_type', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('sex')->default(1);
            $table->integer('animal_category_id')->default(2);
            $table->integer('litter_id')->nullable();
            $table->integer('public_profile')->default(0);
        });
        Schema::create('litters', function (Blueprint $table): void {
            $table->id();
            $table->string('litter_code');
        });
        Schema::create('animal_photo_gallery', function (Blueprint $table): void {
            $table->id();
            $table->integer('animal_id');
        });
        Schema::create('animal_offers', function (Blueprint $table): void {
            $table->id();
            $table->integer('animal_id');
            $table->decimal('price')->nullable();
            $table->timestamps();
        });
        Schema::create('animal_feedings', function (Blueprint $table): void {
            $table->id();
            $table->integer('animal_id');
            $table->integer('feed_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_lists_all_category_two_animals_with_statuses_and_latest_offer(): void
    {
        for ($id = 1; $id <= 105; $id++) {
            DB::table('animals')->insert(['id' => $id, 'name' => 'Snake '.$id]);
        }
        DB::table('animals')->insert(['id' => 106, 'name' => 'Other', 'animal_category_id' => 1]);
        DB::table('litters')->insert(['id' => 1, 'litter_code' => 'M.2026']);
        DB::table('animals')->where('id', 1)->update(['litter_id' => 1, 'public_profile' => 1]);
        DB::table('animal_photo_gallery')->insert(['animal_id' => 1]);
        DB::table('animal_offers')->insert([
            ['animal_id' => 1, 'price' => 100, 'created_at' => '2026-01-01'],
            ['animal_id' => 1, 'price' => 250, 'created_at' => '2026-02-01'],
            ['animal_id' => 2, 'price' => 0, 'created_at' => '2026-02-01'],
        ]);

        $rows = app(GetYoungAnimalsIndexQuery::class)->handle()->keyBy('id');

        $this->assertCount(105, $rows);
        $this->assertFalse($rows->has(106));
        $this->assertSame('M.2026', $rows[1]['litter_code']);
        $this->assertSame('n/sex', $rows[1]['sex_label']);
        $this->assertTrue($rows[1]['has_photo']);
        $this->assertTrue($rows[1]['has_offer']);
        $this->assertTrue($rows[1]['is_public']);
        $this->assertEquals(250, $rows[1]['price']);
        $this->assertEquals(0, $rows[2]['price']);
        $this->assertFalse($rows[3]['has_photo']);
        $this->assertFalse($rows[3]['has_offer']);
        $this->assertFalse($rows[3]['is_public']);
        $this->assertNull($rows[3]['price']);
        $this->assertSame(route('panel.animals.show', 1), $rows[1]['profile_url']);

        $html = view('panel.animals.young', ['animals' => $rows])->render();
        $this->assertStringContainsString('Snake 105', $html);
        $this->assertStringContainsString('250,00 zł', $html);
        $this->assertStringContainsString('0,00 zł', $html);
        $this->assertStringContainsString('n/d', $html);
    }

    public function test_requires_three_latest_valid_feedings_for_each_animal(): void
    {
        $histories = [
            1 => [9, 1, 2, 3],
            2 => [1, 2],
            3 => [1, 9, 2],
            4 => [1, 2, 10],
            5 => [],
            6 => [1, 2, 3],
            7 => [1, null, 2],
        ];
        foreach ($histories as $id => $feeds) {
            DB::table('animals')->insert(['id' => $id, 'name' => 'Snake '.$id]);
            foreach ($feeds as $feedId) {
                DB::table('animal_feedings')->insert([
                    'animal_id' => $id,
                    'feed_id' => $feedId,
                    'created_at' => '2026-01-01 12:00:00',
                ]);
            }
        }
        // An older feeding inserted last must not displace the latest three.
        DB::table('animal_feedings')->insert([
            'animal_id' => 6, 'feed_id' => 10, 'created_at' => '2025-01-01',
        ]);

        $rows = app(GetYoungAnimalsIndexQuery::class)->handle()->keyBy('id');

        foreach ([1 => true, 2 => false, 3 => false, 4 => false, 5 => false, 6 => true, 7 => false] as $id => $expected) {
            $this->assertSame($expected, $rows[$id]['feedings_ok'], 'Animal '.$id);
        }
    }
}