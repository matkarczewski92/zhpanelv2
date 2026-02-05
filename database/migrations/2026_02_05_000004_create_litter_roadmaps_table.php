<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litter_roadmaps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('search_input', 500);
            $table->unsignedTinyInteger('generations')->nullable();
            $table->json('expected_traits');
            $table->boolean('target_reachable')->default(false);
            $table->json('matched_traits');
            $table->json('missing_traits');
            $table->json('steps');
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litter_roadmaps');
    }
};

