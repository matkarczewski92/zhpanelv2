<?php

namespace App\Services\Ewelink;

use App\Models\EwelinkDevice;

class EwelinkDeviceSyncService
{
    public function __construct(private readonly EwelinkCloudClient $client)
    {
    }

    /**
     * @return array{total:int, updated:int, missing:int, errors:int}
     */
    public function syncAll(): array
    {
        $devices = EwelinkDevice::query()
            ->orderBy('id')
            ->get();

        if ($devices->isEmpty()) {
            return [
                'total' => 0,
                'updated' => 0,
                'missing' => 0,
                'errors' => 0,
            ];
        }

        $thingList = $this->client->getThingList();
        $byDeviceId = $this->indexByDeviceId($thingList);

        $updated = 0;
        $missing = 0;
        $errors = 0;
        $needsDelay = false;

        foreach ($devices as $device) {
            $itemData = $byDeviceId[$device->device_id] ?? null;
            $statusPayload = [];
            $lastError = null;
            $apiName = '';

            if (!is_array($itemData)) {
                $missing++;
                $lastError = 'Nie znaleziono urzadzenia w aktualnej liscie eWeLink.';
            } else {
                $apiName = trim((string) ($itemData['name'] ?? ''));

                if ($needsDelay) {
                    usleep(550000);
                }

                $needsDelay = true;

                try {
                    $statusData = $this->client->getThingStatus($device->device_id);
                    $statusPayload = is_array($statusData['params'] ?? null) ? $statusData['params'] : [];
                } catch (\RuntimeException $exception) {
                    $statusPayload = is_array($itemData['params'] ?? null) ? $itemData['params'] : [];
                    $lastError = $exception->getMessage();
                    $errors++;
                }

                $updated++;
            }

            $device->update([
                'name' => $apiName !== '' ? $apiName : $device->name,
                'thing_payload' => $itemData,
                'status_payload' => $statusPayload,
                'last_synced_at' => now(),
                'last_error' => $lastError,
            ]);
        }

        return [
            'total' => $devices->count(),
            'updated' => $updated,
            'missing' => $missing,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<int, mixed> $thingList
     * @return array<string, array<string, mixed>>
     */
    private function indexByDeviceId(array $thingList): array
    {
        $result = [];

        foreach ($thingList as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemData = $item['itemData'] ?? null;
            if (!is_array($itemData)) {
                continue;
            }

            $deviceId = trim((string) ($itemData['deviceid'] ?? ''));
            if ($deviceId === '') {
                continue;
            }

            $result[$deviceId] = $itemData;
        }

        return $result;
    }
}
