<?php

namespace App\Http\Middleware;

use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = trim((string) $request->header('X-API-KEY', ''));

        if ($apiKey === '') {
            return response()->json([
                'message' => 'Brak naglowka X-API-KEY.',
            ], 401);
        }

        $expectedKey = (string) SystemConfig::query()
            ->where('key', 'apiDziennik')
            ->value('value');

        if ($expectedKey === '' || !hash_equals($expectedKey, $apiKey)) {
            return response()->json([
                'message' => 'Niepoprawny klucz API.',
            ], 403);
        }

        return $next($request);
    }
}
