<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('winterings_stage', function (Blueprint $table): void {
            $table->string('scheme')->default('Zimowanie normalne')->after('order');
            $table->index(['scheme', 'order'], 'winterings_stage_scheme_order_index');
        });

        DB::table('winterings_stage')
            ->whereNull('scheme')
            ->orWhere('scheme', '')
            ->update(['scheme' => 'Zimowanie normalne']);
    }

    public function down(): void
    {
        Schema::table('winterings_stage', function (Blueprint $table): void {
            $table->dropIndex('winterings_stage_scheme_order_index');
            $table->dropColumn('scheme');
        });
    }
};

