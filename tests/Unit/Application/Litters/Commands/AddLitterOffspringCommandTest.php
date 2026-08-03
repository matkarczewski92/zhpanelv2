<?php

namespace Tests\Unit\Application\Litters\Commands;

use App\Application\Animals\Services\SecretTagGenerator;
use App\Application\Litters\Commands\AddLitterOffspringCommand;
use App\Models\Litter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddLitterOffspringCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_generates_unique_secret_tags_for_litter_offspring(): void
    {
        $litter = new Litter([
            'litter_code' => 'L.101',
            'hatching_date' => '2026-07-20',
        ]);
        $litter->id = 101;
        $litter->exists = true;

        $command = new AddLitterOffspringCommand(new SecretTagGenerator);
        $command->handle($litter, 2);

        $animals = \DB::table('animals')->orderBy('id')->get();

        $this->assertCount(2, $animals);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', (string) $animals[0]->secret_tag);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', (string) $animals[1]->secret_tag);
        $this->assertNotSame($animals[0]->secret_tag, $animals[1]->secret_tag);
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
            $table->string('secret_tag')->nullable()->unique();
            $table->integer('web_gallery')->nullable();
            $table->timestamps();
        });
    }
}
