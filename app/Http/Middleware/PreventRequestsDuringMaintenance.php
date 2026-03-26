<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as BaseMiddleware;
use Illuminate\Support\Facades\File;

class PreventRequestsDuringMaintenance extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($this->requestIpIsAllowed((string) $request->ip())) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    private function requestIpIsAllowed(string $requestIp): bool
    {
        if ($requestIp === '') {
            return false;
        }

        foreach ($this->allowedIps() as $allowedIp) {
            if ($allowedIp === $requestIp) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function allowedIps(): array
    {
        $path = storage_path('framework/maintenance-allowed-ips.json');

        if (!File::exists($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($ip): bool => is_string($ip) && $ip !== ''));
    }
}
