<?php

namespace Tests\Unit\Application\Animals\Commands;

use App\Application\Animals\Commands\AddWeightCommand;
use App\Application\Animals\Commands\RecordFeedingCommand;
use App\Application\Animals\Commands\RecordMoltCommand;
use App\Application\Animals\Commands\RecordQrFeedingCommand;
use App\Application\Animals\Commands\RecordQrMoltCommand;
use App\Application\Animals\Commands\RecordQrWeightCommand;
use App\Application\Animals\Support\QrAnimalResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QrWorkflowCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        CarbonImmutable::setTestNow('2026-03-27 13:15:00');
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_resolver_accepts_public_profile_url_and_bare_tag(): void
    {
        $animalId = DB::table('animals')->insertGetId([
            'name' => 'Butter',
            'second_name' => 'Stripe',
            'sex' => 3,
            'public_profile_tag' => 'ebe5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolver = new QrAnimalResolver();

        $fromUrl = $resolver->resolve('https://www.makssnake.pl/profile/ebe5');
        $fromTag = $resolver->resolve('ebe5');

        $this->assertSame($animalId, $fromUrl->id);
        $this->assertSame($animalId, $fromTag->id);
    }

    public function test_qr_feeding_requires_duplicate_confirmation_and_uses_default_feed(): void
    {
        $feedId = DB::table('feeds')->insertGetId([
            'name' => 'Mouse XL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $animalId = DB::table('animals')->insertGetId([
            'name' => 'Butter',
            'sex' => 3,
            'feed_id' => $feedId,
            'public_profile_tag' => 'ebe5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('animal_feedings')->insert([
            'animal_id' => $animalId,
            'feed_id' => $feedId,
            'amount' => 1,
            'created_at' => now()->startOfDay()->addHours(9),
            'updated_at' => now()->startOfDay()->addHours(9),
        ]);

        $command = new RecordQrFeedingCommand(new QrAnimalResolver(), new RecordFeedingCommand());

        $duplicate = $command->handle(['payload' => 'https://www.makssnake.pl/profile/ebe5']);
        $confirmed = $command->handle([
            'payload' => 'https://www.makssnake.pl/profile/ebe5',
            'confirm_duplicate' => true,
        ]);

        $this->assertSame('duplicate_confirmation_required', $duplicate->toArray()['status']);
        $this->assertSame('success', $confirmed->toArray()['status']);
        $this->assertDatabaseCount('animal_feedings', 2);
        $this->assertDatabaseHas('animal_feedings', [
            'animal_id' => $animalId,
            'feed_id' => $feedId,
            'amount' => 1,
        ]);
    }

    public function test_qr_weight_requires_duplicate_confirmation_before_second_entry(): void
    {
        $animalId = DB::table('animals')->insertGetId([
            'name' => 'Ghost',
            'sex' => 2,
            'public_profile_tag' => 'w123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('animal_weights')->insert([
            'animal_id' => $animalId,
            'value' => 420.55,
            'created_at' => now()->startOfDay()->addHours(8),
            'updated_at' => now()->startOfDay()->addHours(8),
        ]);

        $command = new RecordQrWeightCommand(new QrAnimalResolver(), new AddWeightCommand());

        $duplicate = $command->handle([
            'payload' => 'w123',
            'value' => 425.10,
        ]);

        $confirmed = $command->handle([
            'payload' => 'w123',
            'value' => 425.10,
            'confirm_duplicate' => true,
        ]);

        $this->assertSame('duplicate_confirmation_required', $duplicate->toArray()['status']);
        $this->assertSame('success', $confirmed->toArray()['status']);
        $this->assertDatabaseCount('animal_weights', 2);
        $this->assertDatabaseHas('animal_weights', [
            'animal_id' => $animalId,
            'value' => 425.10,
        ]);
    }

    public function test_qr_molt_requires_duplicate_confirmation_before_second_entry(): void
    {
        $animalId = DB::table('animals')->insertGetId([
            'name' => 'Amber',
            'sex' => 3,
            'public_profile_tag' => 'molt7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('animal_molts')->insert([
            'animal_id' => $animalId,
            'created_at' => now()->startOfDay()->addHours(7),
            'updated_at' => now()->startOfDay()->addHours(7),
        ]);

        $command = new RecordQrMoltCommand(new QrAnimalResolver(), new RecordMoltCommand());

        $duplicate = $command->handle(['payload' => 'molt7']);
        $confirmed = $command->handle([
            'payload' => 'molt7',
            'confirm_duplicate' => true,
        ]);

        $this->assertSame('duplicate_confirmation_required', $duplicate->toArray()['status']);
        $this->assertSame('success', $confirmed->toArray()['status']);
        $this->assertDatabaseCount('animal_molts', 2);
    }

    public function test_qr_feeding_returns_error_when_default_feed_is_missing(): void
    {
        DB::table('animals')->insert([
            'name' => 'NoFeed',
            'sex' => 3,
            'public_profile_tag' => 'nofeed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $command = new RecordQrFeedingCommand(new QrAnimalResolver(), new RecordFeedingCommand());
        $result = $command->handle(['payload' => 'nofeed']);

        $this->assertSame('error', $result->toArray()['status']);
        $this->assertSame(0, DB::table('animal_feedings')->count());
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('animal_molts');
        Schema::dropIfExists('animal_weights');
        Schema::dropIfExists('animal_feedings');
        Schema::dropIfExists('animals');
        Schema::dropIfExists('feeds');

        Schema::create('feeds', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('second_name')->nullable();
            $table->integer('sex');
            $table->unsignedBigInteger('feed_id')->nullable();
            $table->string('public_profile_tag')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_feedings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('feed_id');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('animal_weights', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->double('value', 8, 2);
            $table->timestamps();
        });

        Schema::create('animal_molts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->timestamps();
        });
    }
}
