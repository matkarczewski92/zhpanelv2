<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('system_config')
            ->where('key', 'apiDziennik')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('system_config')->insert([
            'key' => 'apiDziennik',
            'name' => 'API Dziennik token',
            'value' => bin2hex(random_bytes(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('system_config')
            ->where('key', 'apiDziennik')
            ->delete();
    }
};
