<?php

namespace Tests\Unit\Application\Admin\Commands;

use App\Application\Admin\Commands\GenerateMissingAnimalSecretTagsCommand;
use App\Application\Animals\Services\SecretTagGenerator;
use App\Models\Animal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateMissingAnimalSecretTagsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_generates_only_missing_secret_tags_and_preserves_existing_ones(): void
    {
        $missingNull = $this->createAnimal('Bez tagu null', null);
        $missingEmpty = $this->createAnimal('Bez tagu pusty', '');
        $existing = $this->createAnimal('Z tagiem', 'KEEP77');

        $command = new GenerateMissingAnimalSecretTagsCommand(new SecretTagGenerator);

        $this->assertSame(2, $command->handle());
        $this->assertSame(0, $command->handle());

        $missingNull->refresh();
        $missingEmpty->refresh();
        $existing->refresh();

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', (string) $missingNull->secret_tag);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', (string) $missingEmpty->secret_tag);
        $this->assertNotSame($missingNull->secret_tag, $missingEmpty->secret_tag);
        $this->assertSame('KEEP77', $existing->secret_tag);
    }

    private function createAnimal(string $name, ?string $secretTag): Animal
    {
        return Animal::query()->create([
            'name' => $name,
            'sex' => 1,
            'date_of_birth' => '2026-01-01',
            'secret_tag' => $secretTag,
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
            $table->string('secret_tag')->nullable()->unique();
            $table->integer('web_gallery')->nullable();
            $table->timestamps();
        });
    }
}
