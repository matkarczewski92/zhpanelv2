<?php

namespace Tests\Unit\Application\Animals\Commands;

use App\Application\Animals\Commands\RecordLitterPregnancyShedCommand;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecordLitterPregnancyShedCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_records_pregnancy_shed_for_eligible_female_litter(): void
    {
        $animalId = \DB::table('animals')->insertGetId([
            'name' => 'Female A',
            'sex' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $litterId = \DB::table('litters')->insertGetId([
            'category' => 1,
            'parent_female' => $animalId,
            'season' => 2026,
            'connection_date' => '2026-02-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $command = new RecordLitterPregnancyShedCommand();

        $shed = $command->handle([
            'animal_id' => $animalId,
            'litter_id' => $litterId,
            'shed_date' => '2026-02-14',
        ]);

        $this->assertSame($litterId, (int) $shed->litter_id);
        $this->assertSame('2026-02-14', $shed->shed_date?->format('Y-m-d'));
        $this->assertDatabaseHas('litters_pregnancy_sheds', [
            'id' => $shed->id,
            'litter_id' => $litterId,
            'shed_date' => '2026-02-14',
        ]);
    }

    public function test_rejects_litter_outside_supported_categories(): void
    {
        $animalId = \DB::table('animals')->insertGetId([
            'name' => 'Female B',
            'sex' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $litterId = \DB::table('litters')->insertGetId([
            'category' => 2,
            'parent_female' => $animalId,
            'season' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        (new RecordLitterPregnancyShedCommand())->handle([
            'animal_id' => $animalId,
            'litter_id' => $litterId,
            'shed_date' => '2026-03-01',
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('litters_pregnancy_sheds');
        Schema::dropIfExists('litters');
        Schema::dropIfExists('animals');

        Schema::create('animals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('sex');
            $table->timestamps();
        });

        Schema::create('litters', function (Blueprint $table): void {
            $table->id();
            $table->integer('category');
            $table->unsignedBigInteger('parent_female')->nullable();
            $table->unsignedBigInteger('parent_male')->nullable();
            $table->integer('season')->nullable();
            $table->date('connection_date')->nullable();
            $table->date('planned_connection_date')->nullable();
            $table->date('laying_date')->nullable();
            $table->timestamps();
        });

        Schema::create('litters_pregnancy_sheds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('litter_id');
            $table->date('shed_date');
            $table->timestamps();
        });
    }
}
