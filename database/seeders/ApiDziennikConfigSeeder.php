<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class ApiDziennikConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (SystemConfig::query()->where('key', 'apiDziennik')->exists()) {
            return;
        }

        SystemConfig::query()->create([
            'key' => 'apiDziennik',
            'name' => 'API Dziennik token',
            'value' => bin2hex(random_bytes(32)),
        ]);
    }
}
