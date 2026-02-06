<?php

namespace App\Services\Ewelink;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class EwelinkCloudClient
{
    private const TOKEN_CACHE_KEY = 'ewelink.oauth.tokens';

    public function buildAuthorizationUrl(string $state): string
    {
        $appId = $this->appId();
        $seq = (string) (int) floor(microtime(true) * 1000);
        $message = $appId . '_' . $seq;
        $authorization = $this->hmacBase64($message);

        $query = [
            'clientId' => $appId,
            'seq' => $seq,
            'authorization' => $authorization,
            'redirectUrl' => $this->redirectUrl(),
            'grantType' => 'authorization_code',
            'state' => $state,
            'nonce' => $this->nonce(),
            'showQRCode' => 'false',
        ];

        return (string) config('services.ewelink.oauth_url') . '?' . http_build_query($query);
    }

    /**
     * @return array{
     *     region:string,
     *     access_token:string,
     *     refresh_token:string,
     *     access_token_expires_at:int,
     *     refresh_token_expires_at:int
     * }
     */
    public function exchangeCodeForToken(string $code, ?string $region = null): array
    {
        $resolvedRegion = $this->resolveRegion($region);
        $domain = $this->resolveApiDomain($resolvedRegion);

        $payload = [
            'code' => $code,
            'redirectUrl' => $this->redirectUrl(),
            'grantType' => 'authorization_code',
        ];

        $data = $this->signedPost(
            sprintf('https://%s/v2/user/oauth/token', $domain),
            $payload,
            'Nie udało się wymienić kodu OAuth na token.'
        );

        $accessToken = (string) ($data['accessToken'] ?? '');
        $refreshToken = (string) ($data['refreshToken'] ?? '');
        if ($accessToken === '' || $refreshToken === '') {
            throw new RuntimeException('Odpowiedź eWeLink nie zawiera tokenów dostępu.');
        }

        $token = [
            'region' => $resolvedRegion,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_token_expires_at' => (int) ($data['atExpiredTime'] ?? 0),
            'refresh_token_expires_at' => (int) ($data['rtExpiredTime'] ?? 0),
        ];

        $this->saveToken($token);

        return $token;
    }

    public function hasSavedToken(): bool
    {
        return $this->getSavedToken() !== null;
    }

    public function getSavedRegion(): ?string
    {
        $token = $this->getSavedToken();

        return is_array($token) ? (string) ($token['region'] ?? '') : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getThingList(): array
    {
        [$accessToken, $region] = $this->getValidAccessTokenAndRegion();
        $domain = $this->resolveApiDomain($region);

        $response = Http::timeout(20)
            ->withHeaders($this->bearerHeaders($accessToken))
            ->get(sprintf('https://%s/v2/device/thing', $domain), [
                'num' => 0,
            ]);

        $data = $this->assertSuccess($response, 'Nie udało się pobrać listy urządzeń z eWeLink.');
        $thingList = $data['thingList'] ?? [];

        return is_array($thingList) ? $thingList : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getThingStatus(string $deviceId): array
    {
        [$accessToken, $region] = $this->getValidAccessTokenAndRegion();
        $domain = $this->resolveApiDomain($region);

        $response = Http::timeout(20)
            ->withHeaders($this->bearerHeaders($accessToken))
            ->get(sprintf('https://%s/v2/device/thing/status', $domain), [
                'type' => 1,
                'id' => $deviceId,
            ]);

        return $this->assertSuccess($response, sprintf('Nie udało się pobrać statusu urządzenia %s.', $deviceId));
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function getValidAccessTokenAndRegion(): array
    {
        $token = $this->getSavedToken();
        if (!is_array($token)) {
            throw new RuntimeException('Brak autoryzacji eWeLink. Wykonaj połączenie konta w zakładce Urządzenia.');
        }

        $region = $this->resolveRegion((string) ($token['region'] ?? ''));
        $nowMs = $this->nowMs();
        $expiresAt = (int) ($token['access_token_expires_at'] ?? 0);
        $shouldRefresh = $expiresAt > 0 && $expiresAt <= ($nowMs + 120000);

        if ($shouldRefresh || empty($token['access_token'])) {
            $token = $this->refreshToken($token, $region);
            $this->saveToken($token);
        }

        $accessToken = (string) ($token['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Brak aktywnego tokenu dostępu eWeLink.');
        }

        return [$accessToken, $region];
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    private function refreshToken(array $token, string $region): array
    {
        $refreshToken = (string) ($token['refresh_token'] ?? '');
        if ($refreshToken === '') {
            throw new RuntimeException('Brak refresh token. Połącz konto eWeLink ponownie.');
        }

        $rtExpiresAt = (int) ($token['refresh_token_expires_at'] ?? 0);
        if ($rtExpiresAt > 0 && $rtExpiresAt <= $this->nowMs()) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            throw new RuntimeException('Refresh token eWeLink wygasł. Połącz konto ponownie.');
        }

        $domain = $this->resolveApiDomain($region);
        $data = $this->signedPost(
            sprintf('https://%s/v2/user/refresh', $domain),
            ['rt' => $refreshToken],
            'Nie udało się odświeżyć tokenu eWeLink.'
        );

        $newAccessToken = (string) ($data['at'] ?? '');
        if ($newAccessToken === '') {
            throw new RuntimeException('Odpowiedź eWeLink nie zawiera nowego access tokenu.');
        }

        $newRefreshToken = (string) ($data['rt'] ?? $refreshToken);
        $now = $this->nowMs();

        return [
            'region' => $region,
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'access_token_expires_at' => $now + (30 * 24 * 60 * 60 * 1000),
            'refresh_token_expires_at' => $now + (60 * 24 * 60 * 60 * 1000),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function signedPost(string $url, array $payload, string $fallbackError): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Nie udało się przygotować żądania eWeLink.');
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'X-CK-Appid' => $this->appId(),
                'X-CK-Nonce' => $this->nonce(),
                'Authorization' => 'Sign ' . $this->hmacBase64($body),
                'Content-Type' => 'application/json',
            ])
            ->withBody($body, 'application/json')
            ->send('POST', $url);

        return $this->assertSuccess($response, $fallbackError);
    }

    /**
     * @return array<string, string>
     */
    private function bearerHeaders(string $accessToken): array
    {
        return [
            'X-CK-Appid' => $this->appId(),
            'X-CK-Nonce' => $this->nonce(),
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];
    }

    private function appId(): string
    {
        $appId = (string) config('services.ewelink.app_id', '');
        if ($appId === '') {
            throw new RuntimeException('Brak konfiguracji EWELINK_CLOUD_APP_ID.');
        }

        return $appId;
    }

    private function appSecret(): string
    {
        $appSecret = (string) config('services.ewelink.app_secret', '');
        if ($appSecret === '') {
            throw new RuntimeException('Brak konfiguracji EWELINK_CLOUD_APP_SECRET.');
        }

        return $appSecret;
    }

    private function redirectUrl(): string
    {
        $redirectUrl = (string) config('services.ewelink.redirect_url', '');
        if ($redirectUrl === '') {
            throw new RuntimeException('Brak konfiguracji EWELINK_CLOUD_REDIRECT_URL.');
        }

        return $redirectUrl;
    }

    private function nonce(): string
    {
        return Str::random(8);
    }

    private function hmacBase64(string $message): string
    {
        return base64_encode(hash_hmac('sha256', $message, $this->appSecret(), true));
    }

    /**
     * @return array<string, mixed>
     */
    private function assertSuccess(Response $response, string $fallbackError): array
    {
        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException($fallbackError);
        }

        $error = (int) ($json['error'] ?? -1);
        if ($error !== 0) {
            $message = trim((string) ($json['msg'] ?? ''));
            throw new RuntimeException($message !== '' ? $message : $fallbackError);
        }

        $data = $json['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $token
     */
    private function saveToken(array $token): void
    {
        Cache::forever(self::TOKEN_CACHE_KEY, $token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSavedToken(): ?array
    {
        $value = Cache::get(self::TOKEN_CACHE_KEY);

        return is_array($value) ? $value : null;
    }

    private function resolveRegion(?string $region = null): string
    {
        $resolved = strtolower(trim((string) ($region ?: config('services.ewelink.region', 'eu'))));

        return $resolved === '' ? 'eu' : $resolved;
    }

    private function resolveApiDomain(string $region): string
    {
        $domains = (array) config('services.ewelink.api_domains', []);
        $domain = (string) ($domains[$region] ?? '');
        if ($domain === '') {
            throw new RuntimeException(sprintf('Brak domeny API eWeLink dla regionu "%s".', $region));
        }

        return $domain;
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
