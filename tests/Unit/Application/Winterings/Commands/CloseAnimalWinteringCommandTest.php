<?php

namespace Tests\Unit\Application\Winterings\Commands;

use App\Application\Winterings\Commands\CloseAnimalWinteringCommand;
use App\Application\Winterings\Support\AnimalWinteringCycleResolver;
use App\Models\Wintering;
use App\Models\WinteringStage;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CloseAnimalWinteringCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_closes_active_cycle_and_clears_future_stages(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-22'));

        $animalId = 101;
        $this->createStages();

        Wintering::query()->create([
            'animal_id' => $animalId,
            'season' => 2025,
            'stage_id' => 1,
            'planned_start_date' => '2025-10-01',
            'planned_end_date' => '2025-11-01',
            'start_date' => '2025-10-01',
            'end_date' => '2025-11-01',
        ]);

        $current = Wintering::query()->create([
            'animal_id' => $animalId,
            'season' => 2025,
            'stage_id' => 2,
            'planned_start_date' => '2025-11-01',
            'planned_end_date' => '2026-03-01',
            'start_date' => '2025-11-01',
            'end_date' => null,
        ]);

        $future = Wintering::query()->create([
            'animal_id' => $animalId,
            'season' => 2025,
            'stage_id' => 3,
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-05-01',
            'start_date' => null,
            'end_date' => null,
        ]);

        app(CloseAnimalWinteringCommand::class)->handle($animalId);

        $current->refresh();
        $future->refresh();

        $this->assertSame('2026-02-22', optional($current->end_date)->toDateString());
        $this->assertSame('2026-02-22', optional($current->planned_end_date)->toDateString());
        $this->assertNull($future->start_date);
        $this->assertNull($future->end_date);
        $this->assertNull($future->planned_start_date);
        $this->assertNull($future->planned_end_date);

        $rows = app(AnimalWinteringCycleResolver::class)->resolveCurrentCycleForAnimal($animalId);
        $this->assertFalse(app(AnimalWinteringCycleResolver::class)->isCycleActive($rows));
    }

    public function test_throws_validation_exception_when_cycle_is_not_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-22'));

        $animalId = 202;
        $this->createStages();

        Wintering::query()->create([
            'animal_id' => $animalId,
            'season' => 2025,
            'stage_id' => 1,
            'planned_start_date' => '2025-10-01',
            'planned_end_date' => '2025-11-01',
            'start_date' => '2025-10-01',
            'end_date' => '2025-11-01',
        ]);

        $this->expectException(ValidationException::class);

        app(CloseAnimalWinteringCommand::class)->handle($animalId);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createStages(): void
    {
        WinteringStage::query()->create([
            'id' => 1,
            'scheme' => 'Test',
            'order' => 1,
            'title' => 'Etap 1',
            'duration' => 10,
        ]);

        WinteringStage::query()->create([
            'id' => 2,
            'scheme' => 'Test',
            'order' => 2,
            'title' => 'Etap 2',
            'duration' => 10,
        ]);

        WinteringStage::query()->create([
            'id' => 3,
            'scheme' => 'Test',
            'order' => 3,
            'title' => 'Etap 3',
            'duration' => 10,
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('winterings');
        Schema::dropIfExists('winterings_stage');

        Schema::create('winterings_stage', function (Blueprint $table): void {
            $table->id();
            $table->string('scheme')->nullable();
            $table->integer('order')->default(0);
            $table->string('title')->nullable();
            $table->integer('duration')->default(0);
            $table->timestamps();
        });

        Schema::create('winterings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->integer('season')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('annotations')->nullable();
            $table->unsignedBigInteger('stage_id');
            $table->integer('custom_duration')->nullable();
            $table->string('archive')->nullable();
            $table->timestamps();
        });
    }
}

