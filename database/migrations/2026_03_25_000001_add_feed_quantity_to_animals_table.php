<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->integer('feed_quantity')->default(1)->after('feed_interval');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->dropColumn('feed_quantity');
        });
    }
};
