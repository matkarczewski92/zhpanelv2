<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\EwelinkDevice;
use App\Services\Ewelink\EwelinkCloudClient;
use App\Services\Ewelink\EwelinkDeviceDataFormatter;
use App\Services\Ewelink\EwelinkDeviceSyncService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class DevicesController extends Controller
{
    public function __construct(
        private readonly EwelinkCloudClient $cloudClient,
        private readonly EwelinkDeviceSyncService $syncService,
        private readonly EwelinkDeviceDataFormatter $dataFormatter
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (trim((string) $request->query('code', '')) !== '') {
            return $this->handleOAuthCallback($request);
        }

        $rows = $this->buildRows();

        return view('panel.devices.index', [
            'rows' => $rows,
            'hasToken' => $this->cloudClient->hasSavedToken(),
            'savedRegion' => $this->cloudClient->getSavedRegion(),
        ]);
    }

    public function data(): JsonResponse
    {
        $warning = null;

        try {
            $this->syncWithAutoAuthorization();
        } catch (RuntimeException $exception) {
            $warning = $exception->getMessage();
        }

        $rows = $this->buildRows();
        $rowsHtml = view('panel.devices._rows', ['rows' => $rows])->render();

        return response()->json([
            'rows_html' => $rowsHtml,
            'warning' => $warning,
            'has_token' => $this->cloudClient->hasSavedToken(),
            'saved_region' => $this->cloudClient->getSavedRegion(),
            'server_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        return $this->handleOAuthCallback($request);
    }

    public function toggle(Request $request, EwelinkDevice $device): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string', 'in:on,off'],
        ]);

        $state = strtolower((string) $validated['state']);

        try {
            $this->ensureAuthorized();
            $params = $this->buildToggleParams($device, $state);
            $this->cloudClient->updateThingStatus($device->device_id, $params);
            $this->syncService->syncAll();

            return response()->json([
                'ok' => true,
                'message' => sprintf('Urzadzenie %s ustawione na %s.', $device->name, strtoupper($state)),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function toggleAllSwitches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string', 'in:on,off'],
        ]);

        $state = strtolower((string) $validated['state']);
        $switchDevices = EwelinkDevice::query()
            ->where('device_type', 'switch')
            ->orderBy('id')
            ->get();

        if ($switchDevices->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Brak urzadzen typu przelacznik.',
            ], 422);
        }

        try {
            $this->ensureAuthorized();
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $updated = 0;
        $errors = [];

        foreach ($switchDevices as $device) {
            try {
                $params = $this->buildToggleParams($device, $state);
                $this->cloudClient->updateThingStatus($device->device_id, $params);
                $updated++;
            } catch (RuntimeException $exception) {
                $errors[] = sprintf('%s: %s', $this->deviceDisplayName($device), $exception->getMessage());
            }
        }

        if ($updated > 0) {
            try {
                $this->syncService->syncAll();
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($updated === 0) {
            return response()->json([
                'ok' => false,
                'message' => $errors[0] ?? 'Nie udalo sie zmienic stanu zadnego urzadzenia.',
            ], 422);
        }

        $message = sprintf(
            'Ustawiono stan %s dla %d/%d przelacznikow.',
            strtoupper($state),
            $updated,
            $switchDevices->count()
        );

        if ($errors !== []) {
            $message .= sprintf(' Bledy: %d.', count($errors));
        }

        return response()->json([
            'ok' => true,
            'partial' => $errors !== [],
            'message' => $message,
        ]);
    }

    public function updateSchedule(Request $request, EwelinkDevice $device): JsonResponse
    {
        $validated = $request->validate([
            'schedule' => ['nullable'],
            'human_schedule' => ['nullable', 'array'],
        ]);

        try {
            $humanSchedule = $validated['human_schedule'] ?? null;
            if (is_array($humanSchedule)) {
                $scheduleParams = $this->buildHumanScheduleParamsForDevice($device, $humanSchedule);
            } elseif (array_key_exists('schedule', $validated)) {
                $scheduleParams = $this->normalizeScheduleParams($validated['schedule']);
            } else {
                throw new RuntimeException('Brak danych harmonogramu.');
            }
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        try {
            $this->ensureAuthorized();
            $this->updateDeviceScheduleWithSafety($device, $scheduleParams);

            return response()->json([
                'ok' => true,
                'message' => 'Harmonogram zapisany.',
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function updateScheduleForAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_type' => ['required', 'string', 'in:switch,thermostat'],
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'human_schedule' => ['required', 'array'],
        ]);

        $requestedType = strtolower((string) $validated['device_type']);
        $deviceIds = array_values(array_unique(array_map('intval', $validated['device_ids'] ?? [])));
        $devices = $this->devicesForBulkScheduleType($requestedType, $deviceIds);

        if ($devices->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Brak wybranych urzadzen pasujacych do typu.',
            ], 422);
        }

        try {
            $this->ensureAuthorized();
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $updated = 0;
        $errors = [];
        $humanSchedule = $validated['human_schedule'];

        foreach ($devices as $device) {
            try {
                $scheduleParams = $this->buildHumanScheduleParamsForDevice($device, $humanSchedule);
                $this->updateDeviceScheduleWithSafety($device, $scheduleParams);
                $updated++;
            } catch (RuntimeException $exception) {
                $errors[] = sprintf('%s: %s', $this->deviceDisplayName($device), $exception->getMessage());
            }
        }

        if ($updated === 0) {
            return response()->json([
                'ok' => false,
                'message' => $errors[0] ?? 'Nie udalo sie zapisac harmonogramu.',
            ], 422);
        }

        $deviceLabel = $requestedType === 'switch' ? 'przelacznikach' : 'termostatach';
        $message = sprintf(
            'Zapisano harmonogram w %d/%d %s.',
            $updated,
            $devices->count(),
            $deviceLabel
        );

        if ($errors !== []) {
            $message .= sprintf(' Bledy: %d.', count($errors));
        }

        return response()->json([
            'ok' => true,
            'partial' => $errors !== [],
            'message' => $message,
        ]);
    }

    public function authorize(Request $request): RedirectResponse
    {
        $flow = trim((string) $request->query('flow', ''));

        try {
            $configuredState = trim((string) config('services.ewelink.oauth_state', ''));
            $state = $configuredState !== '' ? $configuredState : Str::random(32);
            $request->session()->put('ewelink.oauth_state', $state);
        } catch (RuntimeException $exception) {
            return redirect()->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        if ($flow !== 'oauth' && $this->cloudClient->hasCredentialAuthConfig()) {
            try {
                $this->cloudClient->authorizeWithCredentials($state);
                $result = $this->syncService->syncAll();

                return redirect()
                    ->route('panel.devices.index')
                    ->with('toast', [
                        'type' => 'success',
                        'message' => sprintf(
                            'Połączono konto eWeLink (backend). Zaktualizowano: %d/%d.',
                            $result['updated'],
                            $result['total']
                        ),
                    ]);
            } catch (RuntimeException $exception) {
                return redirect()->route('panel.devices.index')
                    ->with('toast', [
                        'type' => 'error',
                        'message' => 'Backend OAuth nie powiódł się: ' . $exception->getMessage(),
                    ]);
            }
        }

        try {
            return redirect()->away($this->cloudClient->buildAuthorizationUrl($state));
        } catch (RuntimeException $exception) {
            return redirect()->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    public function refresh(): RedirectResponse
    {
        try {
            $result = $this->syncWithAutoAuthorization();
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $message = sprintf(
            'Synchronizacja zakończona. Zaktualizowano: %d/%d, brak w chmurze: %d.',
            $result['updated'],
            $result['total'],
            $result['missing']
        );

        if ($result['errors'] > 0) {
            $message .= sprintf(' Ostrzeżenia: %d.', $result['errors']);
        }

        return redirect()
            ->route('panel.devices.index')
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * @return array{total:int, updated:int, missing:int, errors:int}
     */
    private function syncWithAutoAuthorization(): array
    {
        $state = trim((string) config('services.ewelink.oauth_state', 'panel'));

        if (!$this->cloudClient->hasSavedToken() && $this->cloudClient->hasCredentialAuthConfig()) {
            $this->cloudClient->authorizeWithCredentials($state);
        }

        try {
            return $this->syncService->syncAll();
        } catch (RuntimeException $exception) {
            if (!$this->cloudClient->hasCredentialAuthConfig()) {
                throw $exception;
            }

            $this->cloudClient->authorizeWithCredentials($state);

            return $this->syncService->syncAll();
        }
    }

    private function handleOAuthCallback(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Brak kodu OAuth w odpowiedzi eWeLink.']);
        }

        $returnedState = trim((string) $request->query('state', ''));
        $sessionState = trim((string) $request->session()->pull('ewelink.oauth_state', ''));
        $configuredState = trim((string) config('services.ewelink.oauth_state', ''));

        if ($sessionState !== '' && $returnedState !== $sessionState) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Niepoprawny parametr state w odpowiedzi OAuth eWeLink.']);
        }

        if ($sessionState === '' && $configuredState !== '' && $returnedState !== $configuredState) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => 'Niepoprawny parametr state w odpowiedzi OAuth eWeLink.']);
        }

        $region = (string) ($request->query('region') ?: $request->query('regin') ?: config('services.ewelink.region', 'eu'));

        try {
            $this->cloudClient->exchangeCodeForToken($code, $region);
            $syncResult = $this->syncService->syncAll();

            $message = sprintf(
                'Połączono konto eWeLink. Zaktualizowano urządzenia: %d/%d.',
                $syncResult['updated'],
                $syncResult['total']
            );

            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'success', 'message' => $message]);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('panel.devices.index')
                ->with('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @return Collection<int, array{device:EwelinkDevice, snapshot:array<string, mixed>}>
     */
    private function buildRows(): Collection
    {
        return EwelinkDevice::query()
            ->orderBy('id')
            ->get()
            ->map(function (EwelinkDevice $device): array {
                return [
                    'device' => $device,
                    'snapshot' => $this->dataFormatter->formatForDevice($device),
                ];
            });
    }

    private function ensureAuthorized(): void
    {
        $state = trim((string) config('services.ewelink.oauth_state', 'panel'));

        if (!$this->cloudClient->hasSavedToken() && $this->cloudClient->hasCredentialAuthConfig()) {
            $this->cloudClient->authorizeWithCredentials($state);
        }

        if (!$this->cloudClient->hasSavedToken()) {
            throw new RuntimeException('Brak autoryzacji eWeLink. Polacz konto na stronie Urzadzenia (przycisk "Polacz konto eWeLink") lub w Ustawieniach portalu -> eWeLink: Urzadzenia.');
        }
    }

    /**
     * @return Collection<int, EwelinkDevice>
     */
    private function devicesForBulkScheduleType(string $requestedType, array $ids = []): Collection
    {
        $query = EwelinkDevice::query()->orderBy('id');
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($requestedType === 'switch') {
            return $query->where('device_type', 'switch')->get();
        }

        return $query
            ->whereIn('device_type', ['thermostat', 'thermostat_hygrostat'])
            ->get();
    }

    /**
     * @param array<string, mixed> $scheduleParams
     */
    private function updateDeviceScheduleWithSafety(EwelinkDevice $device, array $scheduleParams): void
    {
        $deviceParamsBefore = $this->resolveDeviceParams($device);

        $this->cloudClient->updateThingStatus($device->device_id, $scheduleParams);
        $this->syncService->syncAll();

        $fresh = EwelinkDevice::query()->find($device->id);
        $deviceParamsAfter = $fresh ? $this->resolveDeviceParams($fresh) : [];

        if (!$this->scheduleWasUnexpectedlyRemoved($device, $deviceParamsBefore, $deviceParamsAfter)) {
            return;
        }

        $restorePayload = $this->buildScheduleRestorePayload($device, $deviceParamsBefore);
        if ($restorePayload !== null) {
            $this->cloudClient->updateThingStatus($device->device_id, $restorePayload);
            $this->syncService->syncAll();
        }

        throw new RuntimeException(
            'Wykryto usuniecie harmonogramu po zapisie. Przywrocono poprzednie dane z API. Operacja anulowana.'
        );
    }

    private function deviceDisplayName(EwelinkDevice $device): string
    {
        $name = trim((string) $device->name);

        return $name !== '' ? $name : $device->device_id;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function hasMultiSwitches(array $params): bool
    {
        return is_array($params['switches'] ?? null) && $params['switches'] !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildToggleParams(EwelinkDevice $device, string $state): array
    {
        $params = $this->resolveDeviceParams($device);

        if (!$this->hasMultiSwitches($params)) {
            return ['switch' => $state];
        }

        $switches = $params['switches'];
        $normalized = [];

        foreach ($switches as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $outlet = isset($item['outlet']) && is_numeric($item['outlet'])
                ? (int) $item['outlet']
                : (int) $index;

            $normalized[] = [
                'outlet' => $outlet,
                'switch' => $state,
            ];
        }

        if ($normalized === []) {
            return ['switch' => $state];
        }

        return ['switches' => $normalized];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function buildHumanScheduleParamsForDevice(EwelinkDevice $device, array $input): array
    {
        if ($this->isThermostatDevice($device)) {
            return $this->buildThermostatAutoScheduleParams($input);
        }

        return $this->buildSwitchWindowScheduleParams($device, $input);
    }

    private function isThermostatDevice(EwelinkDevice $device): bool
    {
        $type = strtolower(trim((string) $device->device_type));

        return in_array($type, ['thermostat', 'thermostat_hygrostat'], true);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function buildSwitchWindowScheduleParams(EwelinkDevice $device, array $input): array
    {
        $onTime = $this->normalizeLocalTime($input['on_time'] ?? null, 'godzina wlaczenia');
        $offTime = $this->normalizeLocalTime($input['off_time'] ?? null, 'godzina wylaczenia');
        $days = $this->normalizeScheduleDays($input['days'] ?? null);

        $onCron = $this->buildRepeatCronUtc($onTime, $days);
        $offCron = $this->buildRepeatCronUtc($offTime, $days);
        $onCronLocal = $this->buildRepeatCronLocal($onTime, $days);
        $offCronLocal = $this->buildRepeatCronLocal($offTime, $days);

        $params = $this->resolveDeviceParams($device);
        $existingTimers = $this->extractTimersForSchedule($params);
        if ($existingTimers === []) {
            return [
                'timers' => [
                    $this->buildSwitchRepeatTimer([], $device, 'on', $onCron, $onCronLocal),
                    $this->buildSwitchRepeatTimer([], $device, 'off', $offCron, $offCronLocal),
                ],
            ];
        }

        $timers = $existingTimers;
        $onIndex = null;
        $offIndex = null;

        foreach ($timers as $index => $timer) {
            if (!is_array($timer)) {
                continue;
            }

            $state = $this->extractSwitchStateFromTimer($timer);
            if ($state === 'on' && $onIndex === null) {
                $onIndex = $index;
            }

            if ($state === 'off' && $offIndex === null) {
                $offIndex = $index;
            }
        }

        $newOn = $this->buildSwitchRepeatTimer(
            $onIndex !== null && is_array($timers[$onIndex] ?? null) ? $timers[$onIndex] : [],
            $device,
            'on',
            $onCron,
            $onCronLocal
        );
        $newOff = $this->buildSwitchRepeatTimer(
            $offIndex !== null && is_array($timers[$offIndex] ?? null) ? $timers[$offIndex] : [],
            $device,
            'off',
            $offCron,
            $offCronLocal
        );

        if ($onIndex !== null) {
            $timers[$onIndex] = $newOn;
        } else {
            $timers[] = $newOn;
        }

        if ($offIndex !== null) {
            $timers[$offIndex] = $newOff;
        } else {
            $timers[] = $newOff;
        }

        return ['timers' => array_values($timers)];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function buildThermostatAutoScheduleParams(array $input): array
    {
        $rulesInput = $input['rules'] ?? null;
        if (!is_array($rulesInput) || $rulesInput === []) {
            throw new RuntimeException('Dodaj przynajmniej jedna regule Auto.');
        }

        $rules = [];

        foreach ($rulesInput as $index => $ruleInput) {
            if (!is_array($ruleInput)) {
                continue;
            }

            $from = $this->normalizeLocalTime($ruleInput['from'] ?? null, sprintf('godzina startu Auto #%d', ((int) $index) + 1));
            $to = $this->normalizeLocalTime($ruleInput['to'] ?? null, sprintf('godzina konca Auto #%d', ((int) $index) + 1));
            $days = $this->normalizeScheduleDays($ruleInput['days'] ?? null);
            $onTemp = $this->normalizeTemperatureValue($ruleInput['on_temp'] ?? null, sprintf('temperatura wlaczenia Auto #%d', ((int) $index) + 1));
            $offTemp = $this->normalizeTemperatureValue($ruleInput['off_temp'] ?? null, sprintf('temperatura wylaczenia Auto #%d', ((int) $index) + 1));
            $enabled = $this->normalizeBoolean($ruleInput['enabled'] ?? true);

            $rules[] = [
                'enable' => $enabled,
                'effTime' => $this->buildAutoControlEffTime($from, $to, $days),
                'targets' => [
                    [
                        'high' => $offTemp,
                        'reaction' => ['switch' => 'off'],
                    ],
                    [
                        'low' => $onTemp,
                        'reaction' => ['switch' => 'on'],
                    ],
                ],
                'deviceType' => 'temperature',
                'finalStateOfRelay' => 'stay',
            ];
        }

        if ($rules === []) {
            throw new RuntimeException('Niepoprawne reguly Auto.');
        }

        return [
            'autoControlEnabled' => 1,
            'autoControl' => $rules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeScheduleParams(mixed $input): array
    {
        $decoded = $input;

        if (is_string($input)) {
            $json = trim($input);
            if ($json === '') {
                throw new RuntimeException('Puste dane harmonogramu.');
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Niepoprawny JSON harmonogramu.');
            }
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Niepoprawny format harmonogramu.');
        }

        if (array_is_list($decoded)) {
            return ['timers' => $decoded];
        }

        $allowedKeys = ['timers', 'schedules', 'targets', 'workMode', 'workmode', 'workState', 'workstate', 'autoControl', 'autoControlEnabled'];
        $filtered = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $decoded)) {
                $filtered[$key] = $decoded[$key];
            }
        }

        if ($filtered !== []) {
            return $filtered;
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDeviceParams(EwelinkDevice $device): array
    {
        $thingPayload = is_array($device->thing_payload) ? $device->thing_payload : [];
        $statusPayload = is_array($device->status_payload) ? $device->status_payload : [];
        $thingParams = is_array($thingPayload['params'] ?? null) ? $thingPayload['params'] : [];

        return array_replace_recursive($thingParams, $statusPayload);
    }

    /**
     * @param mixed $input
     * @return array<int, int>
     */
    private function normalizeScheduleDays(mixed $input): array
    {
        if (!is_array($input) || $input === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $days = [];
        foreach ($input as $day) {
            if (!is_numeric($day)) {
                continue;
            }

            $value = (int) $day;
            if ($value < 0 || $value > 6) {
                continue;
            }

            $days[] = $value;
        }

        if ($days === []) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }

    private function normalizeLocalTime(mixed $value, string $fieldLabel): string
    {
        $time = trim((string) $value);
        if (preg_match('/^\d{2}:\d{2}$/', $time) !== 1) {
            throw new RuntimeException(sprintf('Niepoprawna %s (format HH:MM).', $fieldLabel));
        }

        return $time;
    }

    private function normalizeTemperatureValue(mixed $value, string $fieldLabel): string
    {
        $raw = str_replace(',', '.', trim((string) $value));
        if ($raw === '' || !is_numeric($raw)) {
            throw new RuntimeException(sprintf('Niepoprawna %s.', $fieldLabel));
        }

        return number_format((float) $raw, 1, '.', '');
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        return (bool) $value;
    }

    /**
     * @param array<int, int> $daysLocal
     * @return array<string, mixed>
     */
    private function buildAutoControlEffTime(string $fromLocal, string $toLocal, array $daysLocal): array
    {
        [$fromUtc, $fromShift] = $this->convertLocalTimeToUtc($fromLocal);
        [$toUtc, $toShift] = $this->convertLocalTimeToUtc($toLocal);

        $daysUtc = $this->shiftWeekdayList($daysLocal, $fromShift);
        if ($toShift !== $fromShift) {
            $daysUtc = $daysLocal;
        }

        return [
            'spanType' => 'range',
            'from' => $fromUtc,
            'to' => $toUtc,
            'days' => $daysUtc,
            'fromLocal' => $fromLocal,
            'toLocal' => $toLocal,
            'daysLocal' => $daysLocal,
        ];
    }

    /**
     * @return array{0:string,1:int}
     */
    private function convertLocalTimeToUtc(string $localTime): array
    {
        [$hourPart, $minutePart] = explode(':', $localTime, 2);
        $localDate = CarbonImmutable::now('Europe/Warsaw')
            ->startOfDay()
            ->setTime((int) $hourPart, (int) $minutePart);

        $utcDate = $localDate->setTimezone('UTC');
        $shift = $utcDate->toDateString() <=> $localDate->toDateString();

        return [$utcDate->format('H:i'), $shift];
    }

    /**
     * @param array<int, int> $days
     * @return array<int, int>
     */
    private function shiftWeekdayList(array $days, int $shift): array
    {
        if ($shift === 0) {
            return $days;
        }

        $shifted = [];
        foreach ($days as $day) {
            $newDay = ($day + $shift) % 7;
            if ($newDay < 0) {
                $newDay += 7;
            }

            $shifted[] = $newDay;
        }

        $shifted = array_values(array_unique($shifted));
        sort($shifted);

        return $shifted;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function extractTimersForSchedule(array $params): array
    {
        $timers = $params['timers'] ?? null;
        if (!is_array($timers) || $timers === []) {
            $timers = $params['schedules'] ?? null;
        }

        if (!is_array($timers)) {
            return [];
        }

        $normalized = [];
        foreach ($timers as $timer) {
            if (is_array($timer)) {
                $normalized[] = $timer;
            }
        }

        return $normalized;
    }

    private function extractSwitchStateFromTimer(array $timer): ?string
    {
        $do = $timer['do'] ?? null;
        if (!is_array($do)) {
            return null;
        }

        $state = strtolower(trim((string) ($do['switch'] ?? '')));
        if (in_array($state, ['on', 'off'], true)) {
            return $state;
        }

        if (!is_array($do['switches'] ?? null)) {
            return null;
        }

        $states = [];
        foreach ($do['switches'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemState = strtolower(trim((string) ($item['switch'] ?? '')));
            if (!in_array($itemState, ['on', 'off'], true)) {
                continue;
            }

            $states[] = $itemState;
        }

        if ($states === []) {
            return null;
        }

        $states = array_values(array_unique($states));

        return count($states) === 1 ? $states[0] : null;
    }

    /**
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private function buildSwitchRepeatTimer(array $base, EwelinkDevice $device, string $state, string $cronUtc, string $cronLocal): array
    {
        $timer = $base;
        $timer['type'] = 'repeat';
        $timer['coolkit_timer_type'] = 'repeat';
        $timer['enabled'] = array_key_exists('enabled', $timer) ? (int) ((bool) $timer['enabled']) : 1;
        $timer['at'] = $cronUtc;
        $timer['atLocal'] = $cronLocal;
        $timer['do'] = $this->buildToggleParams($device, $state);

        if (!array_key_exists('mId', $timer) || trim((string) $timer['mId']) === '') {
            $timer['mId'] = (string) Str::uuid();
        }

        return $timer;
    }

    /**
     * @param array<int, int> $days
     */
    private function buildRepeatCronLocal(string $localTime, array $days): string
    {
        [$hourPart, $minutePart] = explode(':', $localTime, 2);
        $hour = (int) $hourPart;
        $minute = (int) $minutePart;
        $daysExpr = count($days) === 7 ? '*' : implode(',', $days);

        return sprintf('%d %d * * %s', $minute, $hour, $daysExpr);
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function scheduleWasUnexpectedlyRemoved(EwelinkDevice $device, array $before, array $after): bool
    {
        if ($this->isThermostatDevice($device)) {
            $beforeAuto = is_array($before['autoControl'] ?? null) ? $before['autoControl'] : [];
            $afterAuto = is_array($after['autoControl'] ?? null) ? $after['autoControl'] : [];

            return $beforeAuto !== [] && $afterAuto === [];
        }

        $beforeTimers = $this->extractTimersForSchedule($before);
        $afterTimers = $this->extractTimersForSchedule($after);

        return $beforeTimers !== [] && $afterTimers === [];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    private function buildScheduleRestorePayload(EwelinkDevice $device, array $params): ?array
    {
        if ($this->isThermostatDevice($device)) {
            $auto = is_array($params['autoControl'] ?? null) ? $params['autoControl'] : [];
            if ($auto === []) {
                return null;
            }

            return [
                'autoControlEnabled' => (int) (($params['autoControlEnabled'] ?? 1) ? 1 : 0),
                'autoControl' => $auto,
            ];
        }

        $timers = $this->extractTimersForSchedule($params);
        if ($timers === []) {
            return null;
        }

        return ['timers' => $timers];
    }

    /**
     * @param array<int, int> $localDays
     */
    private function buildRepeatCronUtc(string $localTime, array $localDays): string
    {
        [$hourPart, $minutePart] = explode(':', $localTime, 2);
        $hour = (int) $hourPart;
        $minute = (int) $minutePart;

        $utcHour = null;
        $utcMinute = null;
        $utcDays = [];

        foreach ($localDays as $day) {
            $localDate = $this->localDateForWeekday($day)->setTime($hour, $minute);
            $utcDate = $localDate->setTimezone('UTC');

            $utcHour ??= (int) $utcDate->format('G');
            $utcMinute ??= (int) $utcDate->format('i');
            $utcDays[] = (int) $utcDate->format('w');
        }

        $utcDays = array_values(array_unique($utcDays));
        sort($utcDays);
        $daysExpr = count($utcDays) === 7 ? '*' : implode(',', $utcDays);

        return sprintf('%d %d * * %s', $utcMinute ?? 0, $utcHour ?? 0, $daysExpr);
    }

    private function localDateForWeekday(int $weekday): CarbonImmutable
    {
        return CarbonImmutable::now('Europe/Warsaw')
            ->startOfWeek(CarbonInterface::SUNDAY)
            ->addDays($weekday)
            ->startOfDay();
    }
}
