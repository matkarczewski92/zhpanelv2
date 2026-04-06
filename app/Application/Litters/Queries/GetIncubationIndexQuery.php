<?php

namespace App\Application\Litters\Queries;

use App\Application\Litters\Support\IncubationTimelineBuilder;
use App\Models\EwelinkDevice;
use App\Models\Litter;
use App\Models\SystemConfig;
use App\Services\Ewelink\EwelinkDeviceDataFormatter;

class GetIncubationIndexQuery
{
    public function __construct(
        private readonly IncubationTimelineBuilder $incubationTimelineBuilder,
        private readonly EwelinkDeviceDataFormatter $ewelinkDeviceDataFormatter
    ) {
    }

    public function handle(): array
    {
        $litters = Litter::query()
            ->with([
                'maleParent:id,name',
                'femaleParent:id,name',
            ])
            ->whereNotNull('laying_date')
            ->whereNotNull('laying_eggs_ok')
            ->where('laying_eggs_ok', '>', 0)
            ->whereNull('hatching_date')
            ->orderBy('laying_date')
            ->orderBy('id')
            ->get([
                'id',
                'litter_code',
                'season',
                'laying_date',
                'hatching_date',
                'laying_eggs_ok',
                'hatching_eggs',
                'parent_male',
                'parent_female',
            ]);

        $rows = $litters
            ->map(function (Litter $litter): array {
                $timeline = $this->incubationTimelineBuilder->buildActiveBoardForLitter($litter);

                return [
                    'litter' => [
                        'id' => (int) $litter->id,
                        'code' => $this->resolveLitterCode($litter),
                        'season' => $litter->season ? (int) $litter->season : null,
                        'female_name' => $this->sanitizeName($litter->femaleParent?->name),
                        'male_name' => $this->sanitizeName($litter->maleParent?->name),
                        'eggs_to_incubation_label' => $this->formatEggCount($litter->laying_eggs_ok),
                        'hatching_eggs_label' => $this->formatEggCount($litter->hatching_eggs),
                        'profile_url' => route('panel.litters.show', $litter->id),
                    ],
                    'timeline' => $timeline,
                ];
            })
            ->sortBy(fn (array $row) => (int) ($row['timeline']['sort_timestamp'] ?? PHP_INT_MAX))
            ->values()
            ->all();

        return [
            'incubator' => $this->buildIncubatorSummary(),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIncubatorSummary(): array
    {
        $configuredValue = trim((string) SystemConfig::query()->where('key', 'inkubatorId')->value('value'));
        if ($configuredValue === '') {
            return [
                'configured' => false,
                'found' => false,
                'message' => 'Brak skonfigurowanego inkubatora w system_config. Ustaw klucz inkubatorId.',
            ];
        }

        $device = $this->resolveIncubatorDevice($configuredValue);
        if (!$device) {
            return [
                'configured' => true,
                'found' => false,
                'message' => 'Nie znaleziono urzadzenia przypisanego w system_config.inkubatorId.',
            ];
        }

        $snapshot = $this->ewelinkDeviceDataFormatter->formatForDevice($device);
        $onlineState = strtolower(trim((string) ($snapshot['online'] ?? '')));

        [$statusLabel, $statusIcon, $statusClass] = match ($onlineState) {
            'online' => ['online', 'bi-wifi', 'text-success'],
            'offline' => ['offline', 'bi-wifi-off', 'text-danger'],
            default => ['Brak danych', 'bi-question-circle', 'text-muted'],
        };

        return [
            'configured' => true,
            'found' => true,
            'device_name' => trim((string) ($device->name ?? '')) ?: $device->device_id,
            'online' => (string) ($snapshot['online'] ?? '-'),
            'status_label' => $statusLabel,
            'status_icon' => $statusIcon,
            'status_class' => $statusClass,
            'temperature' => (string) ($snapshot['temperature'] ?? '-'),
            'humidity' => (string) ($snapshot['humidity'] ?? '-'),
            'last_synced_at' => $device->last_synced_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    private function resolveIncubatorDevice(string $configuredValue): ?EwelinkDevice
    {
        if (ctype_digit($configuredValue)) {
            $byId = EwelinkDevice::query()->find((int) $configuredValue);
            if ($byId) {
                return $byId;
            }
        }

        return EwelinkDevice::query()
            ->where('device_id', $configuredValue)
            ->first();
    }

    private function resolveLitterCode(Litter $litter): string
    {
        $code = trim((string) ($litter->litter_code ?? ''));

        return $code !== '' ? $code : ('L#' . $litter->id);
    }

    private function sanitizeName(?string $value): string
    {
        $name = trim(strip_tags((string) $value));

        return $name !== '' ? $name : '-';
    }

    private function formatEggCount(?int $value): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format((int) $value, 0, ',', ' ') . ' szt.';
    }
}
