<?php

namespace Tests\Unit\Application\Animals\Commands;

use App\Application\Animals\Commands\RegisterAnimalCommand;
use App\Application\Animals\Services\SecretTagGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisterAnimalCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_defaults_feed_quantity_to_one_when_not_provided(): void
    {
        $command = new RegisterAnimalCommand(new SecretTagGenerator());

        $animal = $command->handle([
            'name' => 'Test snake',
            'sex' => 1,
            'date_of_birth' => '2025-01-10',
            'animal_type_id' => null,
            'animal_category_id' => null,
            'feed_id' => null,
            'feed_interval' => null,
            'public_profile' => 0,
        ]);

        $this->assertSame(1, (int) $animal->feed_quantity);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'feed_quantity' => 1,
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('animals');

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
    }
}
