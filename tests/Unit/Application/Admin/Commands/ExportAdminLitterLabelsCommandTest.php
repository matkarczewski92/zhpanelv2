<?php

namespace Tests\Unit\Application\Admin\Commands;

use App\Application\Admin\Commands\ExportAdminLitterLabelsCommand;
use App\Application\Litters\Support\LitterTimelineCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExportAdminLitterLabelsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Test requires pdo_sqlite extension.');
        }

        $this->prepareSchema();
    }

    public function test_exports_litter_label_rows_to_csv(): void
    {
        Schema::table('litters', function (Blueprint $table): void {
            // No-op to ensure schema is initialized before insert on some sqlite setups.
        });

        \DB::table('litters')->insert([
            'id' => 501,
            'category' => 1,
            'litter_code' => 'L.501',
            'connection_date' => '2026-01-10',
            'laying_date' => '2026-02-09',
            'hatching_date' => null,
            'laying_eggs_total' => 14,
            'laying_eggs_ok' => 11,
            'hatching_eggs' => null,
            'season' => 2026,
            'adnotations' => null,
            'created_at' => null,
            'updated_at' => null,
            'parent_male' => null,
            'parent_female' => null,
            'planned_connection_date' => null,
        ]);

        \DB::table('system_config')->insert([
            ['key' => 'layingDuration', 'value' => '30'],
            ['key' => 'hatchlingDuration', 'value' => '55'],
        ]);

        $command = new ExportAdminLitterLabelsCommand(new LitterTimelineCalculator());

        $result = $command->handle([
            'litter_ids' => [501],
        ]);

        $csv = iconv('Windows-1250', 'UTF-8//IGNORE', $result['content']);

        $this->assertSame('etykiety_mioty_admin.csv', $result['filename']);
        $this->assertStringContainsString(
            'kod_miotu;id_miotu;sezon;data_laczenia;data_zniosu;planowana_data_wyklucia;ilosc_zniesionych_jaj;ilosc_jaj_do_inkubacji',
            $csv
        );
        $this->assertStringContainsString('L.501;501;2026;2026-01-10;2026-02-09;2026-04-05;14;11', $csv);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('litters');
        Schema::dropIfExists('system_config');

        Schema::create('litters', function (Blueprint $table): void {
            $table->id();
            $table->integer('category');
            $table->string('litter_code');
            $table->date('connection_date')->nullable();
            $table->date('laying_date')->nullable();
            $table->date('hatching_date')->nullable();
            $table->integer('laying_eggs_total')->nullable();
            $table->integer('laying_eggs_ok')->nullable();
            $table->integer('hatching_eggs')->nullable();
            $table->integer('season')->nullable();
            $table->text('adnotations')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('parent_male')->nullable();
            $table->unsignedBigInteger('parent_female')->nullable();
            $table->date('planned_connection_date')->nullable();
        });

        Schema::create('system_config', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
}
