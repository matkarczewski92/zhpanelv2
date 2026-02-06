<?php

namespace App\Services\Ewelink;

use App\Models\EwelinkDevice;
use Carbon\CarbonImmutable;

class EwelinkDeviceDataFormatter
{
    private const SOURCE_TIMEZONE = 'UTC';
    private const DISPLAY_TIMEZONE = 'Europe/Warsaw';

    /**
     * @return array{
     *     online:string,
     *     switch:string,
     *     switches:string,
     *     switch_states:array<int, string>,
     *     temperature:string,
     *     humidity:string,
     *     target_temperature:string,
     *     schedule:string,
     *     schedule_lines:array<int, string>,
     *     auto_control_view:array{mode_line:string,lines:array<int, string>},
     *     schedule_edit_params:array<string, mixed>,
     *     schedule_editor:array<string, mixed>,
     *     params_json:string
     * }
     */
    public function formatForDevice(EwelinkDevice $device): array
    {
        $thingPayload = is_array($device->thing_payload) ? $device->thing_payload : [];
        $statusPayload = is_array($device->status_payload) ? $device->status_payload : [];

        $thingParams = is_array($thingPayload['params'] ?? null) ? $thingPayload['params'] : [];
        $params = array_replace_recursive($thingParams, $statusPayload);
        $schedule = $this->resolveSchedule($params);
        $switchStates = $this->extractSwitchStates($params);

        return [
            'online' => $this->resolveOnline($thingPayload),
            'switch' => $this->resolveSwitch($params, $switchStates),
            'switches' => $this->resolveSwitches($params, $switchStates),
            'switch_states' => $switchStates,
            'temperature' => $this->resolveTemperature($params),
            'humidity' => $this->resolveHumidity($params),
            'target_temperature' => $this->resolveTargetTemperature($params),
            'schedule' => $schedule['summary'],
            'schedule_lines' => $schedule['lines'],
            'auto_control_view' => $this->resolveAutoControlView($params),
            'schedule_edit_params' => $this->resolveScheduleEditParams($params),
            'schedule_editor' => $this->resolveScheduleEditor($params, (string) $device->device_type),
            'params_json' => $this->encodeParams($params),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function resolveScheduleEditParams(array $params): array
    {
        $result = [];
        foreach (['timers', 'schedules', 'targets', 'workMode', 'workmode', 'workState', 'workstate'] as $key) {
            if (array_key_exists($key, $params)) {
                $result[$key] = $params[$key];
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function resolveScheduleEditor(array $params, string $deviceType): array
    {
        $normalizedDeviceType = strtolower(trim($deviceType));
        $isThermostat = in_array($normalizedDeviceType, ['thermostat', 'thermostat_hygrostat'], true);

        if ($isThermostat) {
            $rules = $this->resolveAutoControlEditorRules($params);
            if ($rules === []) {
                $rules = [[
                    'days' => [0, 1, 2, 3, 4, 5, 6],
                    'from' => '09:00',
                    'to' => '21:00',
                    'on_temp' => '25.0',
                    'off_temp' => '25.5',
                ]];
            }

            return [
                'kind' => 'thermostat_auto',
                'rules' => $rules,
            ];
        }

        $timers = $params['timers'] ?? null;
        if (!is_array($timers) || $timers === []) {
            $timers = $params['schedules'] ?? null;
        }

        $onTime = '';
        $offTime = '';
        $days = [0, 1, 2, 3, 4, 5, 6];

        if (is_array($timers)) {
            foreach ($timers as $timer) {
                if (!is_array($timer)) {
                    continue;
                }

                $type = strtolower((string) ($timer['coolkit_timer_type'] ?? $timer['type'] ?? ''));
                if ($type !== 'repeat') {
                    continue;
                }

                $at = trim((string) ($timer['at'] ?? ''));
                $parsed = $this->parseRepeatAtToEditor($at);
                if ($parsed === null) {
                    continue;
                }

                $state = $this->extractSwitchStateForEditor($timer['do'] ?? null);
                if ($state === null) {
                    continue;
                }

                $days = $parsed['days'];
                if ($state === 'on' && $onTime === '') {
                    $onTime = $parsed['time'];
                } elseif ($state === 'off' && $offTime === '') {
                    $offTime = $parsed['time'];
                }
            }
        }

        return [
            'kind' => 'switch_window',
            'on_time' => $onTime !== '' ? $onTime : '09:00',
            'off_time' => $offTime !== '' ? $offTime : '21:00',
            'days' => $days,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array{days:array<int, int>,from:string,to:string,on_temp:string,off_temp:string}>
     */
    private function resolveAutoControlEditorRules(array $params): array
    {
        $autoControl = $params['autoControl'] ?? null;
        if (!is_array($autoControl) || $autoControl === []) {
            return [];
        }

        $rules = [];
        foreach ($autoControl as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $effTime = is_array($rule['effTime'] ?? null) ? $rule['effTime'] : [];
            $from = trim((string) ($effTime['fromLocal'] ?? $effTime['from'] ?? ''));
            $to = trim((string) ($effTime['toLocal'] ?? $effTime['to'] ?? ''));
            if ($from === '' || $to === '') {
                continue;
            }

            [$onTemp, $offTemp] = $this->extractAutoOnOffThresholds($rule['targets'] ?? null);

            $rules[] = [
                'days' => $this->normalizeWeekdayArray($effTime['daysLocal'] ?? $effTime['days'] ?? null),
                'from' => $this->normalizeTimeValue($from, '09:00'),
                'to' => $this->normalizeTimeValue($to, '21:00'),
                'on_temp' => $onTemp ?? '25.0',
                'off_temp' => $offTemp ?? '25.5',
            ];
        }

        return $rules;
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
     * @param array<int, string> $switchStates
     */
    private function resolveSwitch(array $params, array $switchStates = []): string
    {
        $single = $this->findValueByKeys($params, ['switch']);
        if (is_string($single) && $single !== '') {
            return $single;
        }

        if ($switchStates === []) {
            return '-';
        }

        $uniqueStates = array_values(array_unique(array_values($switchStates)));

        return count($uniqueStates) === 1 ? (string) $uniqueStates[0] : 'mixed';
    }

    /**
     * @param array<string, mixed> $params
     * @param array<int, string> $switchStates
     */
    private function resolveSwitches(array $params, array $switchStates = []): string
    {
        if ($switchStates === []) {
            return '-';
        }

        $parts = [];
        foreach ($switchStates as $channel => $state) {
            $parts[] = sprintf('ch%s:%s', $channel, $state);
        }

        return implode(' | ', $parts);
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

        return $this->formatNumericWithUnit($value, ' C');
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

        return $this->formatNumericWithUnit($value, ' %');
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

        $direct = $this->formatNumericWithUnit($directValue, ' C');
        if ($direct !== '-') {
            return $direct;
        }

        $targets = $params['targets'] ?? null;
        if (is_array($targets) && !empty($targets)) {
            $first = $targets[0] ?? null;
            if (is_array($first)) {
                $range = $this->formatTargetRange($first);
                if ($range !== '-') {
                    return $range;
                }
            }
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $params
     * @return array{summary:string, lines:array<int, string>}
     */
    private function resolveSchedule(array $params): array
    {
        $lines = [];

        $timers = $params['timers'] ?? null;
        if (!is_array($timers) || $timers === []) {
            $timers = $params['schedules'] ?? null;
        }

        if (is_array($timers)) {
            foreach ($timers as $timer) {
                if (!is_array($timer)) {
                    continue;
                }

                $line = $this->formatTimerLine($timer);
                if ($line !== null) {
                    $lines[] = $line;
                }
            }
        }

        foreach ($this->resolveThermostatAutomationLines($params) as $line) {
            $lines[] = $line;
        }

        foreach ($this->resolveAutoControlLines($params) as $line) {
            $lines[] = $line;
        }

        if ($lines === []) {
            return [
                'summary' => '-',
                'lines' => [],
            ];
        }

        $summaryLines = array_slice($lines, 0, 2);
        $summary = implode(' | ', $summaryLines);
        if (count($lines) > 2) {
            $summary .= sprintf(' (+%d)', count($lines) - 2);
        }

        return [
            'summary' => $summary,
            'lines' => $lines,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, string>
     */
    private function resolveAutoControlLines(array $params): array
    {
        $autoControl = $params['autoControl'] ?? null;
        if (!is_array($autoControl) || $autoControl === []) {
            return [];
        }

        $lines = [];
        foreach ($autoControl as $index => $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $enabled = !array_key_exists('enable', $rule) || (bool) $rule['enable'];
            $prefix = $enabled ? '' : '[OFF] ';

            $effTime = is_array($rule['effTime'] ?? null) ? $rule['effTime'] : [];
            $window = $this->formatAutoControlWindow($effTime);
            $conditions = $this->formatAutoControlConditions($rule['targets'] ?? null);

            $parts = [];
            if ($window !== '-') {
                $parts[] = $window;
            }
            if ($conditions !== '-') {
                $parts[] = $conditions;
            }

            if ($parts === []) {
                continue;
            }

            $lines[] = $prefix . sprintf('Auto #%d: %s', ((int) $index) + 1, implode(' | ', $parts));
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{mode_line:string,lines:array<int, string>}
     */
    private function resolveAutoControlView(array $params): array
    {
        $autoControl = $params['autoControl'] ?? null;
        if (!is_array($autoControl) || $autoControl === []) {
            return ['mode_line' => '', 'lines' => []];
        }

        $lines = [];
        $dayLabels = [];

        foreach ($autoControl as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $enabled = !array_key_exists('enable', $rule) || (bool) $rule['enable'];
            $effTime = is_array($rule['effTime'] ?? null) ? $rule['effTime'] : [];
            $daysLabel = $this->formatWeekdaysFromArray($effTime['daysLocal'] ?? $effTime['days'] ?? null);
            $dayLabels[] = $daysLabel;

            $from = trim((string) ($effTime['fromLocal'] ?? $effTime['from'] ?? ''));
            $to = trim((string) ($effTime['toLocal'] ?? $effTime['to'] ?? ''));
            if ($from === '' || $to === '') {
                continue;
            }

            $window = sprintf('%s-%s', $from, $to);

            [$onTemp, $offTemp] = $this->extractAutoOnOffThresholds($rule['targets'] ?? null);
            $line = $window;
            if ($onTemp !== null || $offTemp !== null) {
                $line .= sprintf(' On: %sstC - Off: %sstC', $onTemp ?? '-', $offTemp ?? '-');
            }

            if (!$enabled) {
                $line = '[OFF] ' . $line;
            }

            $lines[] = $line;
        }

        if ($lines === []) {
            return ['mode_line' => '', 'lines' => []];
        }

        $dayLabels = array_values(array_unique(array_map(static fn (string $label): string => trim($label), $dayLabels)));
        if ($dayLabels === []) {
            $modeLine = 'Tryb: Auto';
        } elseif (count($dayLabels) === 1) {
            $modeLine = 'Tryb: Auto, ' . ucfirst($dayLabels[0]);
        } else {
            $modeLine = 'Tryb: Auto, Rozne dni';
        }

        return [
            'mode_line' => $modeLine,
            'lines' => $lines,
        ];
    }

    /**
     * @param array<string, mixed> $timer
     */
    private function formatTimerLine(array $timer): ?string
    {
        $type = strtolower((string) ($timer['coolkit_timer_type'] ?? $timer['type'] ?? 'timer'));
        $at = trim((string) ($timer['at'] ?? ''));
        $enabled = !array_key_exists('enabled', $timer) || (bool) $timer['enabled'];

        $prefix = $enabled ? '' : '[OFF] ';

        if ($type === 'repeat') {
            $timeAndDays = $this->formatRepeatAt($at);
            $action = $this->formatTimerAction($timer);

            return $prefix . trim(sprintf('Powtarzaj: %s -> %s', $timeAndDays, $action));
        }

        if ($type === 'once') {
            $when = $this->formatOnceAt($at);
            $action = $this->formatTimerAction($timer);

            return $prefix . trim(sprintf('Jednorazowo: %s -> %s', $when, $action));
        }

        if ($type === 'duration') {
            $action = $this->formatDurationAction($timer);
            $start = $this->formatDurationAt($at);

            return $prefix . trim(sprintf('Petla: %s -> %s', $start, $action));
        }

        $action = $this->formatTimerAction($timer);

        return $prefix . trim(sprintf('%s: %s -> %s', $type, $at !== '' ? $at : '-', $action));
    }

    /**
     * @param array<string, mixed> $timer
     */
    private function formatTimerAction(array $timer): string
    {
        $do = $timer['do'] ?? null;
        if (is_array($do)) {
            return $this->formatSwitchActionFromParams($do);
        }

        return '-';
    }

    /**
     * @param array<string, mixed> $effTime
     */
    private function formatAutoControlWindow(array $effTime): string
    {
        $from = trim((string) ($effTime['fromLocal'] ?? $effTime['from'] ?? ''));
        $to = trim((string) ($effTime['toLocal'] ?? $effTime['to'] ?? ''));

        if ($from === '' || $to === '') {
            return '-';
        }

        $daysRaw = $effTime['daysLocal'] ?? $effTime['days'] ?? null;
        $daysLabel = $this->formatWeekdaysFromArray($daysRaw);
        $crossMidnight = $this->isCrossMidnight($from, $to);

        $timeWindow = $crossMidnight
            ? sprintf('%s-%s (+1)', $from, $to)
            : sprintf('%s-%s', $from, $to);

        return trim($daysLabel . ' ' . $timeWindow);
    }

    private function formatAutoControlConditions(mixed $targets): string
    {
        if (!is_array($targets) || $targets === []) {
            return '-';
        }

        $parts = [];
        foreach ($targets as $target) {
            if (!is_array($target)) {
                continue;
            }

            $reaction = is_array($target['reaction'] ?? null) ? $target['reaction'] : [];
            $switch = strtoupper(trim((string) ($reaction['switch'] ?? '')));

            if ($switch === '') {
                $switch = strtoupper(trim((string) ($target['switch'] ?? '')));
            }

            if ($this->isNumericValue($target['high'] ?? null)) {
                $parts[] = sprintf('T > %s C -> %s', $this->formatFloat((float) $target['high']), $switch !== '' ? $switch : '-');
            }

            if ($this->isNumericValue($target['low'] ?? null)) {
                $parts[] = sprintf('T < %s C -> %s', $this->formatFloat((float) $target['low']), $switch !== '' ? $switch : '-');
            }
        }

        if ($parts === []) {
            return '-';
        }

        return implode(' | ', $parts);
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private function extractAutoOnOffThresholds(mixed $targets): array
    {
        if (!is_array($targets) || $targets === []) {
            return [null, null];
        }

        $onTemp = null;
        $offTemp = null;

        foreach ($targets as $target) {
            if (!is_array($target)) {
                continue;
            }

            $reaction = is_array($target['reaction'] ?? null) ? $target['reaction'] : [];
            $switch = strtolower(trim((string) ($reaction['switch'] ?? $target['switch'] ?? '')));

            $high = $this->formatThresholdValue($target['high'] ?? $target['targetHigh'] ?? null);
            $low = $this->formatThresholdValue($target['low'] ?? $target['targetLow'] ?? null);

            if ($switch === 'on') {
                if ($onTemp === null && $low !== null) {
                    $onTemp = $low;
                    continue;
                }

                if ($onTemp === null && $high !== null) {
                    $onTemp = $high;
                }
            }

            if ($switch === 'off') {
                if ($offTemp === null && $high !== null) {
                    $offTemp = $high;
                    continue;
                }

                if ($offTemp === null && $low !== null) {
                    $offTemp = $low;
                }
            }
        }

        return [$onTemp, $offTemp];
    }

    private function formatThresholdValue(mixed $value): ?string
    {
        if (!$this->isNumericValue($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw !== '' && is_numeric($raw)) {
            return $raw;
        }

        return $this->formatFloat((float) $value);
    }

    /**
     * @param array<string, mixed> $timer
     */
    private function formatDurationAction(array $timer): string
    {
        $startDo = is_array($timer['startDo'] ?? null) ? $timer['startDo'] : [];
        $endDo = is_array($timer['endDo'] ?? null) ? $timer['endDo'] : [];

        $start = $this->formatSwitchActionFromParams($startDo);
        $end = $this->formatSwitchActionFromParams($endDo);

        if ($start === '-' && $end === '-') {
            return '-';
        }

        return sprintf('start %s / end %s', $start, $end);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, string>
     */
    private function resolveThermostatAutomationLines(array $params): array
    {
        $lines = [];

        $targets = $params['targets'] ?? null;
        if (is_array($targets)) {
            foreach ($targets as $index => $target) {
                if (!is_array($target)) {
                    continue;
                }

                $parts = [];
                $range = $this->formatTargetRange($target);
                if ($range !== '-') {
                    $parts[] = 'zakres ' . $range;
                }

                $reaction = trim((string) ($target['reaction'] ?? $params['reaction'] ?? ''));
                if ($reaction !== '') {
                    $parts[] = 'reakcja ' . $reaction;
                }

                $switch = trim((string) ($target['switch'] ?? ''));
                if ($switch !== '') {
                    $parts[] = 'switch ' . $switch;
                }

                if ($parts !== []) {
                    $lines[] = sprintf('Termostat #%d: %s', $index + 1, implode(', ', $parts));
                }
            }
        }

        $workMode = trim((string) ($params['workMode'] ?? $params['workmode'] ?? ''));
        if ($workMode !== '') {
            $lines[] = 'Tryb pracy: ' . $workMode;
        }

        $workState = trim((string) ($params['workState'] ?? $params['workstate'] ?? ''));
        if ($workState !== '') {
            $lines[] = 'Stan pracy: ' . $workState;
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $target
     */
    private function formatTargetRange(array $target): string
    {
        $low = $target['targetLow'] ?? $target['low'] ?? null;
        $high = $target['targetHigh'] ?? $target['high'] ?? null;

        if ($this->isNumericValue($low) && $this->isNumericValue($high)) {
            return sprintf('%s-%s C', $this->formatFloat((float) $low), $this->formatFloat((float) $high));
        }

        if ($this->isNumericValue($low)) {
            return sprintf('>= %s C', $this->formatFloat((float) $low));
        }

        if ($this->isNumericValue($high)) {
            return sprintf('<= %s C', $this->formatFloat((float) $high));
        }

        return '-';
    }

    private function formatRepeatAt(string $at): string
    {
        $parts = preg_split('/\s+/', trim($at));
        if (!is_array($parts) || count($parts) < 5) {
            return $at !== '' ? $at : '-';
        }

        $minute = $parts[0];
        $hour = $parts[1];
        $days = $parts[4];

        [$hour, $minute, $days] = $this->convertCronUtcToDisplay($hour, $minute, $days);
        $time = $this->formatHourMinute((string) $hour, (string) $minute);
        $dayLabel = $this->formatCronDays((string) $days);

        return trim($dayLabel . ' ' . $time);
    }

    /**
     * @return array{time:string,days:array<int, int>}|null
     */
    private function parseRepeatAtToEditor(string $at): ?array
    {
        $parts = preg_split('/\s+/', trim($at));
        if (!is_array($parts) || count($parts) < 5) {
            return null;
        }

        $minute = $parts[0];
        $hour = $parts[1];
        $days = $parts[4];

        [$displayHour, $displayMinute, $displayDays] = $this->convertCronUtcToDisplay($hour, $minute, $days);
        if (!is_numeric($displayHour) || !is_numeric($displayMinute)) {
            return null;
        }

        return [
            'time' => sprintf('%02d:%02d', (int) $displayHour, (int) $displayMinute),
            'days' => $this->parseCronDaysToArray($displayDays),
        ];
    }

    private function formatOnceAt(string $at): string
    {
        if ($at === '') {
            return '-';
        }

        $local = $this->parseUtcToDisplay($at);
        if ($local !== null) {
            return $local->format('Y-m-d H:i');
        }

        $ts = strtotime($at);

        return $ts === false ? $at : date('Y-m-d H:i', $ts);
    }

    private function formatDurationAt(string $at): string
    {
        $parts = preg_split('/\s+/', trim($at));
        if (!is_array($parts) || $parts === []) {
            return '-';
        }

        $startRaw = (string) $parts[0];
        $localStart = $this->parseUtcToDisplay($startRaw);
        if ($localStart !== null) {
            $start = $localStart->format('Y-m-d H:i');
        } else {
            $startTs = strtotime($startRaw);
            $start = $startTs === false ? $startRaw : date('Y-m-d H:i', $startTs);
        }

        $firstDelay = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;
        $secondDelay = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;

        if ($firstDelay === null && $secondDelay === null) {
            return $start;
        }

        return sprintf(
            '%s (po %s min / %s min)',
            $start,
            $firstDelay !== null ? $firstDelay : '-',
            $secondDelay !== null ? $secondDelay : '-'
        );
    }

    private function formatHourMinute(string $hour, string $minute): string
    {
        if (!is_numeric($hour) || !is_numeric($minute)) {
            return trim($hour . ':' . $minute, ':');
        }

        return sprintf('%02d:%02d', (int) $hour, (int) $minute);
    }

    private function formatCronDays(string $days): string
    {
        $value = trim($days);
        if ($value === '' || $value === '*') {
            return 'codziennie';
        }

        $dayMap = [
            0 => 'nd',
            1 => 'pn',
            2 => 'wt',
            3 => 'sr',
            4 => 'cz',
            5 => 'pt',
            6 => 'sb',
        ];

        $dayNumbers = [];
        foreach (explode(',', $value) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || !is_numeric($chunk)) {
                return $value;
            }

            $num = (int) $chunk;
            if ($num === 7) {
                $num = 0;
            }

            if ($num < 0 || $num > 6) {
                return $value;
            }

            $dayNumbers[] = $num;
        }

        $dayNumbers = array_values(array_unique($dayNumbers));
        sort($dayNumbers);

        if ($dayNumbers === [0, 1, 2, 3, 4, 5, 6]) {
            return 'codziennie';
        }

        if ($dayNumbers === [1, 2, 3, 4, 5]) {
            return 'pn-pt';
        }

        if ($dayNumbers === [0, 6]) {
            return 'weekend';
        }

        $labels = [];
        foreach ($dayNumbers as $day) {
            $labels[] = $dayMap[$day] ?? (string) $day;
        }

        return implode(',', $labels);
    }

    private function formatWeekdaysFromArray(mixed $days): string
    {
        $normalized = $this->normalizeWeekdayArray($days);

        if ($normalized === [0, 1, 2, 3, 4, 5, 6]) {
            return 'codziennie';
        }

        if ($normalized === [1, 2, 3, 4, 5]) {
            return 'pn-pt';
        }

        if ($normalized === [0, 6]) {
            return 'weekend';
        }

        $labels = [
            0 => 'nd',
            1 => 'pn',
            2 => 'wt',
            3 => 'sr',
            4 => 'cz',
            5 => 'pt',
            6 => 'sb',
        ];

        $result = [];
        foreach ($normalized as $day) {
            $result[] = $labels[$day] ?? (string) $day;
        }

        return implode(',', $result);
    }

    /**
     * @return array<int, int>
     */
    private function normalizeWeekdayArray(mixed $days): array
    {
        if (!is_array($days) || $days === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $normalized = [];
        foreach ($days as $day) {
            if (!is_numeric($day)) {
                continue;
            }

            $num = (int) $day;
            if ($num === 7) {
                $num = 0;
            }

            if ($num < 0 || $num > 6) {
                continue;
            }

            $normalized[] = $num;
        }

        if ($normalized === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function normalizeTimeValue(string $value, string $fallback): string
    {
        $trimmed = trim($value);
        if (preg_match('/^\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed;
        }

        return $fallback;
    }

    private function isCrossMidnight(string $from, string $to): bool
    {
        if (preg_match('/^\d{2}:\d{2}$/', $from) !== 1 || preg_match('/^\d{2}:\d{2}$/', $to) !== 1) {
            return false;
        }

        [$fromHour, $fromMinute] = array_map('intval', explode(':', $from));
        [$toHour, $toMinute] = array_map('intval', explode(':', $to));

        $fromTotal = ($fromHour * 60) + $fromMinute;
        $toTotal = ($toHour * 60) + $toMinute;

        return $toTotal <= $fromTotal;
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function convertCronUtcToDisplay(string $hour, string $minute, string $days): array
    {
        if (!is_numeric($hour) || !is_numeric($minute)) {
            return [$hour, $minute, $days];
        }

        $source = CarbonImmutable::now(self::SOURCE_TIMEZONE)
            ->startOfDay()
            ->setTime((int) $hour, (int) $minute);
        $local = $source->setTimezone(self::DISPLAY_TIMEZONE);
        $dayShift = (int) $source->diffInDays($local, false);

        $shiftedDays = $this->shiftCronDays($days, $dayShift);

        return [$local->format('H'), $local->format('i'), $shiftedDays];
    }

    private function shiftCronDays(string $days, int $shift): string
    {
        $value = trim($days);
        if ($value === '' || $value === '*' || $shift === 0) {
            return $days;
        }

        $shifted = [];
        foreach (explode(',', $value) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || !is_numeric($chunk)) {
                return $days;
            }

            $day = (int) $chunk;
            if ($day === 7) {
                $day = 0;
            }

            if ($day < 0 || $day > 6) {
                return $days;
            }

            $newDay = ($day + $shift) % 7;
            if ($newDay < 0) {
                $newDay += 7;
            }

            $shifted[] = $newDay;
        }

        $shifted = array_values(array_unique($shifted));
        sort($shifted);

        return implode(',', $shifted);
    }

    /**
     * @return array<int, int>
     */
    private function parseCronDaysToArray(string $days): array
    {
        $value = trim($days);
        if ($value === '' || $value === '*') {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $result = [];
        foreach (explode(',', $value) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || !is_numeric($chunk)) {
                continue;
            }

            $day = (int) $chunk;
            if ($day === 7) {
                $day = 0;
            }

            if ($day < 0 || $day > 6) {
                continue;
            }

            $result[] = $day;
        }

        if ($result === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $result = array_values(array_unique($result));
        sort($result);

        return $result;
    }

    private function parseUtcToDisplay(string $value): ?CarbonImmutable
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('/(Z|z|[+-]\d{2}:\d{2})$/', $raw) === 1) {
                return CarbonImmutable::parse($raw)->setTimezone(self::DISPLAY_TIMEZONE);
            }

            return CarbonImmutable::parse($raw, self::SOURCE_TIMEZONE)->setTimezone(self::DISPLAY_TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, string>
     */
    private function extractSwitchStates(array $params): array
    {
        if (!is_array($params['switches'] ?? null)) {
            return [];
        }

        $states = [];

        foreach ($params['switches'] as $index => $switchData) {
            if (!is_array($switchData)) {
                continue;
            }

            $state = trim((string) ($switchData['switch'] ?? ''));
            if ($state === '') {
                continue;
            }

            $channel = isset($switchData['outlet']) && is_numeric($switchData['outlet'])
                ? ((int) $switchData['outlet']) + 1
                : ((int) $index) + 1;

            $states[$channel] = $state;
        }

        ksort($states);

        return $states;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function formatSwitchActionFromParams(array $params): string
    {
        $single = trim((string) ($params['switch'] ?? ''));
        if ($single !== '') {
            return strtoupper($single);
        }

        if (!is_array($params['switches'] ?? null)) {
            return '-';
        }

        $parts = [];
        foreach ($params['switches'] as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $state = trim((string) ($item['switch'] ?? ''));
            if ($state === '') {
                continue;
            }

            $channel = isset($item['outlet']) && is_numeric($item['outlet'])
                ? ((int) $item['outlet']) + 1
                : ((int) $index) + 1;

            $parts[] = sprintf('ch%s:%s', $channel, strtoupper($state));
        }

        return $parts === [] ? '-' : implode(', ', $parts);
    }

    private function extractSwitchStateForEditor(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $single = strtolower(trim((string) ($payload['switch'] ?? '')));
        if (in_array($single, ['on', 'off'], true)) {
            return $single;
        }

        if (!is_array($payload['switches'] ?? null)) {
            return null;
        }

        $states = [];
        foreach ($payload['switches'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $state = strtolower(trim((string) ($item['switch'] ?? '')));
            if (!in_array($state, ['on', 'off'], true)) {
                continue;
            }

            $states[] = $state;
        }

        if ($states === []) {
            return null;
        }

        $states = array_values(array_unique($states));

        return count($states) === 1 ? $states[0] : null;
    }

    private function formatNumericWithUnit(mixed $value, string $unit): string
    {
        if (!$this->isNumericValue($value)) {
            return '-';
        }

        return $this->formatFloat((float) $value) . $unit;
    }

    private function formatFloat(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private function isNumericValue(mixed $value): bool
    {
        return is_numeric($value) || (is_string($value) && trim($value) !== '' && is_numeric($value));
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
