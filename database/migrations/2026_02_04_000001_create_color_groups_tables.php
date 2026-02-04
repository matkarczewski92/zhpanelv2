<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('color_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('animal_color_group', function (Blueprint $table): void {
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('color_group_id')->constrained('color_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['animal_id', 'color_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_color_group');
        Schema::dropIfExists('color_groups');
    }
};
