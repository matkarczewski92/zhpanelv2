<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ewelink_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('device_type', 40)->default('switch');
            $table->json('thing_payload')->nullable();
            $table->json('status_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ewelink_devices');
    }
};
