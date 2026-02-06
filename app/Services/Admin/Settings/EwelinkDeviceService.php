<?php

namespace App\Services\Admin\Settings;

use App\Models\EwelinkDevice;

class EwelinkDeviceService
{
    public function store(array $data): EwelinkDevice
    {
        return EwelinkDevice::create($data);
    }

    public function update(EwelinkDevice $device, array $data): EwelinkDevice
    {
        $device->update($data);

        return $device;
    }

    public function destroy(EwelinkDevice $device): void
    {
        $device->delete();
    }
}
