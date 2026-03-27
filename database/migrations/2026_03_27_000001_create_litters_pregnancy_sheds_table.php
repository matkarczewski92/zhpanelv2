<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litters_pregnancy_sheds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('litter_id');
            $table->date('shed_date');
            $table->timestamps();

            $table->index(['litter_id', 'shed_date'], 'litters_pregnancy_sheds_litter_date_idx');
            $table->foreign('litter_id', 'litters_pregnancy_sheds_litter_fk')
                ->references('id')
                ->on('litters')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litters_pregnancy_sheds');
    }
};
