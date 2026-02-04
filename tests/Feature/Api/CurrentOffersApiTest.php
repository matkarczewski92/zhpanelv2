<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrentOffersApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_open_endpoint_returns_current_public_offers(): void
    {
        DB::table('animals')->insert([
            [
                'id' => 1,
                'name' => '<b>Ultramel</b>',
                'sex' => 2,
                'date_of_birth' => '2022-08-30',
                'public_profile' => 1,
                'public_profile_tag' => 'QHPU4R',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Hidden',
                'sex' => 3,
                'date_of_birth' => '2022-01-01',
                'public_profile' => 0,
                'public_profile_tag' => 'HIDDEN1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Sold',
                'sex' => 3,
                'date_of_birth' => '2021-01-01',
                'public_profile' => 1,
                'public_profile_tag' => 'SOLD01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('animal_offers')->insert([
            [
                'id' => 11,
                'animal_id' => 1,
                'price' => 2500.00,
                'sold_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'animal_id' => 2,
                'price' => 1000.00,
                'sold_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 13,
                'animal_id' => 3,
                'price' => 900.00,
                'sold_date' => '2025-11-02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('animal_offer_reservations')->insert([
            'offer_id' => 11,
            'deposit' => 500.00,
            'booker' => 'Tester',
            'adnotations' => null,
            'expiration_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('animal_photo_gallery')->insert([
            'animal_id' => 1,
            'url' => '/uploads/ultramel.jpg',
            'main_profil_photo' => 1,
            'banner_possition' => null,
            'webside' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/offers/current');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.offer_id', 11)
            ->assertJsonPath('data.0.animal_id', 1)
            ->assertJsonPath('data.0.name', 'Ultramel')
            ->assertJsonPath('data.0.sex', 2)
            ->assertJsonPath('data.0.sex_label', 'samiec')
            ->assertJsonPath('data.0.has_reservation', true)
            ->assertJsonPath('data.0.date_of_birth', '2022-08-30')
            ->assertJsonPath('data.0.main_photo_url', 'https://makssnake.pl/uploads/ultramel.jpg')
            ->assertJsonPath('data.0.public_profile_url', 'https://www.makssnake.pl/profile/QHPU4R')
            ->assertJsonPath('data.0.price', function ($price): bool {
                return abs((float) $price - 2500.00) < 0.0001;
            });
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('animal_offer_reservations');
        Schema::dropIfExists('animal_offers');
        Schema::dropIfExists('animal_photo_gallery');
        Schema::dropIfExists('animals');

        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('sex')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('public_profile')->default(0);
            $table->string('public_profile_tag')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->double('price', 8, 2)->nullable();
            $table->date('sold_date')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_offer_reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->double('deposit', 8, 2)->nullable();
            $table->string('booker')->nullable();
            $table->text('adnotations')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_photo_gallery', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->string('url')->nullable();
            $table->unsignedTinyInteger('main_profil_photo')->default(0);
            $table->integer('banner_possition')->nullable();
            $table->unsignedTinyInteger('webside')->default(0);
            $table->timestamps();
        });
    }
}
