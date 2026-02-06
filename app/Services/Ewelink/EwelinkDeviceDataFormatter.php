<?php

namespace App\Services\Ewelink;

use App\Models\EwelinkDevice;

class EwelinkDeviceDataFormatter
{
    /**
     * @return array{
     *     online:string,
     *     switch:string,
     *     temperature:string,
     *     humidity:string,
     *     target_temperature:string,
     *     schedule:string,
     *     params_json:string
     * }
     */
    public function formatForDevice(EwelinkDevice $device): array
    {
        $thingPayload = is_array($device->thing_payload) ? $device->thing_payload : [];
        $statusPayload = is_array($device->status_payload) ? $device->status_payload : [];

        $thingParams = is_array($thingPayload['params'] ?? null) ? $thingPayload['params'] : [];
        $params = array_replace_recursive($thingParams, $statusPayload);

        return [
            'online' => $this->resolveOnline($thingPayload),
            'switch' => $this->resolveSwitch($params),
            'temperature' => $this->resolveTemperature($params),
            'humidity' => $this->resolveHumidity($params),
            'target_temperature' => $this->resolveTargetTemperature($params),
            'schedule' => $this->resolveSchedule($params),
            'params_json' => $this->encodeParams($params),
        ];
    }

    /**
     * @param array<string, mixed> $thingPayload
     */
    private function resolveOnline(array $thingPayload): string
    {
        if (!array_key_exists('online', $thingPayload)) {
            return '-';
        }

        return (bool) $thingPayload['online'] ? 'online' : 'offline';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveSwitch(array $params): string
    {
        $single = $this->findValueByKeys($params, ['switch']);
        if (is_string($single) && $single !== '') {
            return $single;
        }

        if (is_array($params['switches'] ?? null)) {
            $parts = [];
            foreach ($params['switches'] as $index => $switchData) {
                if (!is_array($switchData)) {
                    continue;
                }

                $state = $switchData['switch'] ?? null;
                if (!is_string($state) || $state === '') {
                    continue;
                }

                $parts[] = sprintf('ch%s:%s', $index + 1, $state);
            }

            if (!empty($parts)) {
                return implode(', ', $parts);
            }
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveTemperature(array $params): string
    {
        $value = $this->findValueByKeys($params, [
            'currentTemperature',
            'temperature',
            'temp',
            'currentTemp',
            'tempValue',
        ]);

        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') . ' °C';
        }

        if (is_string($value) && trim($value) !== '' && is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') . ' °C';
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveHumidity(array $params): string
    {
        $value = $this->findValueByKeys($params, [
            'currentHumidity',
            'humidity',
            'humidityValue',
        ]);

        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') . ' %';
        }

        if (is_string($value) && trim($value) !== '' && is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') . ' %';
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveTargetTemperature(array $params): string
    {
        $directValue = $this->findValueByKeys($params, [
            'targetTemperature',
            'targetTemp',
            'tempTarget',
            'temperatureSetpoint',
            'setTemp',
        ]);

        if (is_numeric($directValue) || (is_string($directValue) && is_numeric($directValue))) {
            return rtrim(rtrim(number_format((float) $directValue, 1, '.', ''), '0'), '.') . ' °C';
        }

        $targets = $params['targets'] ?? null;
        if (is_array($targets) && !empty($targets)) {
            $first = $targets[0] ?? null;
            if (is_array($first)) {
                $low = $first['targetLow'] ?? null;
                $high = $first['targetHigh'] ?? null;

                if (is_numeric($low) && is_numeric($high)) {
                    return sprintf(
                        '%s - %s °C',
                        rtrim(rtrim(number_format((float) $low, 1, '.', ''), '0'), '.'),
                        rtrim(rtrim(number_format((float) $high, 1, '.', ''), '0'), '.')
                    );
                }
            }
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveSchedule(array $params): string
    {
        $timers = $params['timers'] ?? null;
        if (!is_array($timers) || $timers === []) {
            $timers = $params['schedules'] ?? null;
        }

        if (!is_array($timers) || $timers === []) {
            return '-';
        }

        $summaries = [];
        foreach (array_slice($timers, 0, 2) as $timer) {
            if (!is_array($timer)) {
                continue;
            }

            $type = (string) ($timer['coolkit_timer_type'] ?? $timer['type'] ?? 'timer');
            $at = (string) ($timer['at'] ?? '');
            $switchState = (string) ($timer['do']['switch'] ?? $timer['startDo']['switch'] ?? '');

            $piece = trim(sprintf('%s %s %s', $type, $at, $switchState));
            if ($piece !== '') {
                $summaries[] = $piece;
            }
        }

        if (empty($summaries)) {
            return sprintf('%d wpis(y)', count($timers));
        }

        $suffix = count($timers) > 2 ? ' ...' : '';

        return implode(' | ', $summaries) . $suffix;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function encodeParams(array $params): string
    {
        if ($params === []) {
            return '{}';
        }

        $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $json === false ? '{}' : $json;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $keys
     */
    private function findValueByKeys(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findValueByKeys($value, $keys);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
