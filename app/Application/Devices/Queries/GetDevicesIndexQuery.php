<?php

namespace App\Application\Devices\Queries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GetDevicesIndexQuery
{
    /**
     * @param array{device?:string|null} $filters
     * @return array{
     *   selected_device:string,
     *   devices:array<int, string>,
     *   cloud:array{
     *     region:string,
     *     base_url:string,
     *     area_code:string,
     *     email:string,
     *     app_id_configured:bool,
     *     app_secret_configured:bool,
     *     password_configured:bool,
     *     oauth_code_configured:bool,
     *     access_token_configured:bool,
     *     complete:bool
     *   },
     *   ok:bool,
     *   error:string,
     *   telemetry:array{temperature_current:float|null,temperature_unit:string,thermostat_mode:string,target_low:float|null,target_high:float|null},
     *   payloads:array<string, mixed>
     * }
     */
    public function handle(array $filters = []): array
    {
        $cloudConfig = $this->buildCloudConfigView();
        $configuredDevices = collect((array) config('services.ewelink_cloud.device_serials', []))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedDevice = trim((string) ($filters['device'] ?? ''));
        if ($selectedDevice === '') {
            $selectedDevice = (string) ($configuredDevices[0] ?? '');
        }

        $result = [
            'selected_device' => $selectedDevice,
            'devices' => $configuredDevices,
            'cloud' => $cloudConfig,
            'ok' => false,
            'error' => '',
            'telemetry' => [
                'temperature_current' => null,
                'temperature_unit' => '',
                'thermostat_mode' => '',
                'target_low' => null,
                'target_high' => null,
            ],
            'payloads' => [
                'cloud_dispatch' => null,
                'cloud_base_candidates' => [],
                'cloud_base_selected' => null,
                'cloud_login_attempts' => [],
                'cloud_login' => null,
                'cloud_devices' => null,
                'cloud_status' => null,
                'cloud_status_post' => null,
            ],
        ];

        return $this->loadFromCloud($result, $selectedDevice, $configuredDevices);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<int, string> $configuredDevices
     * @return array<string, mixed>
     */
    private function loadFromCloud(array $result, string $selectedDevice, array $configuredDevices): array
    {
        $cloud = (array) config('services.ewelink_cloud', []);
        $appId = trim((string) ($cloud['app_id'] ?? ''));
        $appSecret = trim((string) ($cloud['app_secret'] ?? ''));
        $email = trim((string) ($cloud['email'] ?? ''));
        $password = (string) ($cloud['password'] ?? '');
        $areaCode = trim((string) ($cloud['area_code'] ?? '+48'));
        $region = trim((string) ($cloud['region'] ?? 'eu'));
        $manualBaseUrl = trim((string) ($cloud['base_url'] ?? ''));
        $oauthCode = trim((string) ($cloud['oauth_code'] ?? ''));
        $redirectUrl = trim((string) ($cloud['redirect_url'] ?? ''));
        $presetAccessToken = trim((string) ($cloud['access_token'] ?? ''));

        if (!$result['cloud']['complete']) {
            $result['error'] = 'Brak pelnej konfiguracji cloud API (APP_ID/APP_SECRET/EMAIL/PASSWORD).';
            return $result;
        }

        try {
            $baseCandidates = $this->buildCloudBaseCandidates($region, $appId, $manualBaseUrl, $result);
            $result['payloads']['cloud_base_candidates'] = $baseCandidates;

            [$baseUrl, $tokenResponse, $tokenData, $accessToken] = $this->tryCloudAccessToken(
                $baseCandidates,
                $appId,
                $appSecret,
                $presetAccessToken,
                $oauthCode,
                $redirectUrl,
                $email,
                $password,
                $areaCode,
                $result
            );
            $result['payloads']['cloud_base_selected'] = $baseUrl;
            $result['payloads']['cloud_login'] = $tokenData;

            if ($accessToken === '') {
                $msg = (string) ($tokenData['msg'] ?? '');
                if (str_contains(strtolower($msg), 'path of request is not allowed')) {
                    $result['error'] = 'APPID jest typu OAuth2 i nie ma dostepu do /v2/user/login. '
                        . 'Uzyj OAuth code flow (/v2/user/oauth/token) i ustaw EWELINK_CLOUD_OAUTH_CODE.';
                    return $result;
                }

                $result['error'] = 'Pobranie tokena Cloud API nie powiodlo sie.';
                return $result;
            }

            $authHeaders = [
                'Authorization' => 'Bearer ' . $accessToken,
                'X-CK-Appid' => $appId,
                'Accept' => 'application/json',
            ];

            $devicesResponse = Http::timeout(12)
                ->withHeaders($authHeaders)
                ->get($baseUrl . '/v2/device/thing', [
                    'num' => 100,
                    'page' => 1,
                ]);
            $devicesData = (array) ($devicesResponse->json() ?? []);
            $result['payloads']['cloud_devices'] = $devicesData;

            if (!$devicesResponse->successful()) {
                $result['error'] = 'Nie udalo sie pobrac listy urzadzen Cloud API.';
                return $result;
            }

            $cloudThings = $this->extractCloudThingList($devicesData);
            $cloudIds = collect($cloudThings)
                ->map(fn (array $item): string => $this->extractCloudThingId($item))
                ->filter()
                ->values()
                ->all();

            $result['devices'] = collect($configuredDevices)
                ->merge($cloudIds)
                ->merge($selectedDevice !== '' ? [$selectedDevice] : [])
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($selectedDevice === '') {
                $selectedDevice = (string) ($result['devices'][0] ?? '');
            }
            $result['selected_device'] = $selectedDevice;

            $selectedThing = $this->findCloudThingById($cloudThings, $selectedDevice);
            $statusQuery = [
                'id' => $selectedDevice,
                'type' => 1,
            ];

            $statusResponse = Http::timeout(12)
                ->withHeaders($authHeaders)
                ->get($baseUrl . '/v2/device/thing/status', $statusQuery);
            $statusData = (array) ($statusResponse->json() ?? []);
            $result['payloads']['cloud_status'] = $statusData;

            if (!$statusResponse->successful()) {
                $statusPostResponse = Http::timeout(12)
                    ->withHeaders($authHeaders)
                    ->post($baseUrl . '/v2/device/thing/status', $statusQuery);
                $statusPostData = (array) ($statusPostResponse->json() ?? []);
                $result['payloads']['cloud_status_post'] = $statusPostData;

                if ($statusPostResponse->successful()) {
                    $statusData = $statusPostData;
                }
            }

            $result['telemetry'] = $this->extractTelemetry([$statusData, $selectedThing, $devicesData]);
            $result['ok'] = $selectedThing !== null;

            if (!$result['ok']) {
                $result['error'] = 'Nie znaleziono wybranego urzadzenia w Cloud API.';
            }
        } catch (\Throwable $e) {
            $result['error'] = 'Blad polaczenia z Cloud API: ' . $e->getMessage()
                . ' (podpowiedz: ustaw EWELINK_CLOUD_BASE_URL, np. https://eu-apia.coolkit.cc lub https://eu-pconnect.coolkit.cc)';
        }

        return $result;
    }

    /**
     * Dispatch service may return dedicated API host for the app/region.
     *
     * @param array<string, mixed> $result
     */
    private function resolveCloudDispatchHost(string $region, string $appId, array &$result): ?string
    {
        $normalized = strtolower(trim($region));

        if (!in_array($normalized, ['eu', 'us', 'as', 'cn'], true)) {
            $normalized = 'eu';
        }

        $dispatchUrl = sprintf('https://%s-dispa.coolkit.cc/dispatch/app', $normalized);
        $dispatchPayload = [
            'region' => $normalized,
            'appId' => $appId,
        ];

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    'X-CK-Appid' => $appId,
                ])
                ->get($dispatchUrl, $dispatchPayload);
            $body = (array) ($response->json() ?? []);
            $result['payloads']['cloud_dispatch'] = [
                'url' => $dispatchUrl,
                'status' => $response->status(),
                'body' => $body,
            ];

            if ($response->successful()) {
                $host = $this->findFirstScalarByKeys(
                    [$body],
                    ['domain', 'apiDomain', 'host', 'endpoint', 'url']
                );

                if (is_scalar($host) && trim((string) $host) !== '') {
                    $rawHost = trim((string) $host);
                    if (str_starts_with($rawHost, 'http://') || str_starts_with($rawHost, 'https://')) {
                        return rtrim($rawHost, '/');
                    }

                    return 'https://' . rtrim($rawHost, '/');
                }
            }
        } catch (\Throwable) {
            // fallback below
        }

        return null;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<int, string>
     */
    private function buildCloudBaseCandidates(string $region, string $appId, string $manualBaseUrl, array &$result): array
    {
        $normalized = strtolower(trim($region));
        if (!in_array($normalized, ['eu', 'us', 'as', 'cn'], true)) {
            $normalized = 'eu';
        }

        $dispatchHost = $this->resolveCloudDispatchHost($normalized, $appId, $result);
        $hosts = collect([
            $manualBaseUrl !== '' ? rtrim($manualBaseUrl, '/') : null,
            sprintf('https://%s-apia.coolkit.cc', $normalized),
            sprintf('https://%s-apia.coolkit.cn', $normalized),
            $dispatchHost,
        ])->filter()->values();

        // Dispatch often returns pconnect host; keep it, but also force matching apia host as fallback.
        if (is_string($dispatchHost) && str_contains($dispatchHost, '-pconnect')) {
            $apiaFromDispatch = str_replace('-pconnect', '-apia', $dispatchHost);
            $hosts->push($apiaFromDispatch);
        }

        return $hosts->unique()->values()->all();
    }

    /**
     * @param array<int, string> $baseCandidates
     * @param array<string, mixed> $result
     * @return array{0:string,1:mixed,2:array<string,mixed>,3:string}
     */
    private function tryCloudAccessToken(
        array $baseCandidates,
        string $appId,
        string $appSecret,
        string $presetAccessToken,
        string $oauthCode,
        string $redirectUrl,
        string $email,
        string $password,
        string $areaCode,
        array &$result
    ): array {
        if ($presetAccessToken !== '') {
            $result['payloads']['cloud_login_attempts'] = [[
                'base_url' => '(preset token)',
                'status' => 200,
                'mode' => 'preset_access_token',
                'error' => null,
            ]];

            return [$baseCandidates[0] ?? '', null, ['mode' => 'preset_access_token'], $presetAccessToken];
        }

        $attempts = [];
        $lastResponse = null;
        $lastData = [];

        foreach ($baseCandidates as $baseUrl) {
            try {
                [$response, $data, $mode] = $this->requestTokenForBaseUrl(
                    $baseUrl,
                    $appId,
                    $appSecret,
                    $oauthCode,
                    $redirectUrl,
                    $email,
                    $password,
                    $areaCode
                );

                $attempts[] = [
                    'base_url' => $baseUrl,
                    'status' => $response->status(),
                    'mode' => $mode,
                    'error' => $data['msg'] ?? $data['message'] ?? null,
                ];

                $lastResponse = $response;
                $lastData = $data;

                $token = $this->extractAccessToken($data);
                if ($response->successful() && $token !== '') {
                    $result['payloads']['cloud_login_attempts'] = $attempts;
                    return [$baseUrl, $response, $data, $token];
                }
            } catch (\Throwable $e) {
                $attempts[] = [
                    'base_url' => $baseUrl,
                    'status' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $result['payloads']['cloud_login_attempts'] = $attempts;
        return [$baseCandidates[0] ?? '', $lastResponse, $lastData, ''];
    }

    /**
     * @return array{0:mixed,1:array<string,mixed>,2:string}
     */
    private function requestTokenForBaseUrl(
        string $baseUrl,
        string $appId,
        string $appSecret,
        string $oauthCode,
        string $redirectUrl,
        string $email,
        string $password,
        string $areaCode
    ): array {
        if ($oauthCode !== '') {
            $body = array_filter([
                'grantType' => 'authorization_code',
                'code' => $oauthCode,
                'redirectUrl' => $redirectUrl !== '' ? $redirectUrl : null,
            ], static fn (mixed $v): bool => $v !== null && $v !== '');

            $response = $this->signedPost($baseUrl . '/v2/user/oauth/token', $appId, $appSecret, $body);
            return [$response, (array) ($response->json() ?? []), 'oauth_token'];
        }

        $body = [
            'countryCode' => $areaCode,
            'account' => $email,
            'password' => $password,
        ];
        $response = $this->signedPost($baseUrl . '/v2/user/login', $appId, $appSecret, $body);

        return [$response, (array) ($response->json() ?? []), 'legacy_login'];
    }

    /**
     * @param array<string,mixed> $body
     */
    private function signedPost(string $url, string $appId, string $appSecret, array $body): mixed
    {
        $timestamp = (string) ((int) round(microtime(true) * 1000));
        $nonce = Str::lower(Str::random(8));
        $payload = (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', $payload, $appSecret, true));

        return Http::timeout(12)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Sign ' . $signature,
                'X-CK-Appid' => $appId,
                'X-CK-Nonce' => $nonce,
                'X-CK-Timestamp' => $timestamp,
            ])
            ->post($url, $body);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractAccessToken(array $payload): string
    {
        return trim((string) (
            $payload['data']['at']
            ?? $payload['data']['token']
            ?? $payload['data']['accessToken']
            ?? $payload['at']
            ?? $payload['token']
            ?? $payload['accessToken']
            ?? ''
        ));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractCloudThingList(array $payload): array
    {
        $candidates = [
            $payload['data']['thingList'] ?? null,
            $payload['data']['things'] ?? null,
            $payload['data']['list'] ?? null,
            $payload['thingList'] ?? null,
            $payload['things'] ?? null,
            $payload['list'] ?? null,
            $payload['data'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && isset($candidate[0]) && is_array($candidate[0])) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $thing
     */
    private function extractCloudThingId(array $thing): string
    {
        $identifier = $this->findFirstScalarByKeys([$thing], ['deviceid', 'deviceId', 'serialNumber', 'serial_number', 'id']);

        return is_scalar($identifier) ? trim((string) $identifier) : '';
    }

    /**
     * @param array<int, array<string, mixed>> $things
     */
    private function findCloudThingById(array $things, string $id): ?array
    {
        $needle = trim($id);
        if ($needle === '') {
            return $things[0] ?? null;
        }

        foreach ($things as $thing) {
            if ($this->extractCloudThingId($thing) === $needle) {
                return $thing;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $sources
     * @return array{temperature_current:float|null,temperature_unit:string,thermostat_mode:string,target_low:float|null,target_high:float|null}
     */
    private function extractTelemetry(array $sources): array
    {
        return [
            'temperature_current' => $this->findFirstNumericByKeys(
                $sources,
                ['currentTemperature', 'temperature', 'temperatureValue', 'current-temperature', 'current_value', 'value', 'temp']
            ),
            'temperature_unit' => (string) ($this->findFirstScalarByKeys(
                $sources,
                ['temperatureUnit', 'tempUnit', 'unit', 'temperature-unit']
            ) ?? 'C'),
            'thermostat_mode' => (string) ($this->findFirstScalarByKeys(
                $sources,
                ['thermostat-mode', 'thermostatMode', 'mode', 'workingMode', 'switch']
            ) ?? ''),
            'target_low' => $this->findFirstNumericByKeys(
                $sources,
                ['lowerSetpoint', 'minSetpoint', 'minimum', 'min', 'targetLow', 'low', 'targetTempMin']
            ),
            'target_high' => $this->findFirstNumericByKeys(
                $sources,
                ['upperSetpoint', 'maxSetpoint', 'maximum', 'max', 'targetHigh', 'high', 'targetTempMax', 'targetTemperature', 'targetTemp']
            ),
        ];
    }

    /**
     * @return array{
     *   region:string,
     *   base_url:string,
     *   area_code:string,
     *   email:string,
     *   app_id_configured:bool,
     *   app_secret_configured:bool,
     *   password_configured:bool,
     *   oauth_code_configured:bool,
     *   access_token_configured:bool,
     *   complete:bool
     * }
     */
    private function buildCloudConfigView(): array
    {
        $cloud = (array) config('services.ewelink_cloud', []);
        $email = trim((string) ($cloud['email'] ?? ''));
        $appIdConfigured = trim((string) ($cloud['app_id'] ?? '')) !== '';
        $appSecretConfigured = trim((string) ($cloud['app_secret'] ?? '')) !== '';
        $passwordConfigured = trim((string) ($cloud['password'] ?? '')) !== '';
        $oauthCodeConfigured = trim((string) ($cloud['oauth_code'] ?? '')) !== '';
        $accessTokenConfigured = trim((string) ($cloud['access_token'] ?? '')) !== '';
        $hasCredential = $accessTokenConfigured || $oauthCodeConfigured || ($email !== '' && $passwordConfigured);

        return [
            'region' => trim((string) ($cloud['region'] ?? 'eu')),
            'base_url' => trim((string) ($cloud['base_url'] ?? '')),
            'area_code' => trim((string) ($cloud['area_code'] ?? '+48')),
            'email' => $email !== '' ? $email : '-',
            'app_id_configured' => $appIdConfigured,
            'app_secret_configured' => $appSecretConfigured,
            'password_configured' => $passwordConfigured,
            'oauth_code_configured' => $oauthCodeConfigured,
            'access_token_configured' => $accessTokenConfigured,
            'complete' => $appIdConfigured && $appSecretConfigured && $hasCredential,
        ];
    }

    /**
     * @param array<int, mixed> $sources
     * @param array<int, string> $keys
     */
    private function findFirstNumericByKeys(array $sources, array $keys): ?float
    {
        foreach ($sources as $source) {
            foreach ($keys as $key) {
                $value = $this->findRecursiveByKey($source, $key);
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $sources
     * @param array<int, string> $keys
     */
    private function findFirstScalarByKeys(array $sources, array $keys): mixed
    {
        foreach ($sources as $source) {
            foreach ($keys as $key) {
                $value = $this->findRecursiveByKey($source, $key);
                if (is_scalar($value) && trim((string) $value) !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function findRecursiveByKey(mixed $payload, string $needle): mixed
    {
        if (!is_array($payload)) {
            return null;
        }

        foreach ($payload as $key => $value) {
            if (is_string($key) && strcasecmp($key, $needle) === 0) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findRecursiveByKey($value, $needle);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
