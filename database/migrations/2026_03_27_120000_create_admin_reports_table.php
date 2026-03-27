<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('report_type');
            $table->string('report_name');
            $table->dateTime('generated_at');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->date('report_date')->nullable();
            $table->unsignedInteger('item_count')->nullable();
            $table->string('file_name');
            $table->string('pdf_path');
            $table->json('filters_payload')->nullable();
            $table->json('report_payload')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'generated_at']);
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_reports');
    }
};
