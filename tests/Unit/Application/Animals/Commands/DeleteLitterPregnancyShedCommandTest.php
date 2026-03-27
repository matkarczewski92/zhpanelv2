<?php

namespace Tests\Unit\Application\Animals\Commands;

use App\Application\Animals\Commands\DeleteLitterPregnancyShedCommand;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeleteLitterPregnancyShedCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_deletes_pregnancy_shed_for_matching_female(): void
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shedId = \DB::table('litters_pregnancy_sheds')->insertGetId([
            'litter_id' => $litterId,
            'shed_date' => '2026-03-27',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new DeleteLitterPregnancyShedCommand())->handle($animalId, $shedId);

        $this->assertDatabaseMissing('litters_pregnancy_sheds', [
            'id' => $shedId,
        ]);
    }

    public function test_rejects_deleting_shed_for_other_female(): void
    {
        $ownerAnimalId = \DB::table('animals')->insertGetId([
            'name' => 'Female A',
            'sex' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherAnimalId = \DB::table('animals')->insertGetId([
            'name' => 'Female B',
            'sex' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $litterId = \DB::table('litters')->insertGetId([
            'category' => 1,
            'parent_female' => $ownerAnimalId,
            'season' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shedId = \DB::table('litters_pregnancy_sheds')->insertGetId([
            'litter_id' => $litterId,
            'shed_date' => '2026-03-27',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        (new DeleteLitterPregnancyShedCommand())->handle($otherAnimalId, $shedId);
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
