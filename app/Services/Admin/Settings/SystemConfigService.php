<?php

namespace App\Services\Admin\Settings;

use App\Models\SystemConfig;

class SystemConfigService
{
    public function store(array $data): SystemConfig
    {
        return SystemConfig::create($data);
    }

    public function update(SystemConfig $config, array $data): SystemConfig
    {
        $config->update($data);
        return $config;
    }

    public function destroy(SystemConfig $config): void
    {
        $config->delete();
    }
}
