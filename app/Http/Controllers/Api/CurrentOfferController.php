<?php

namespace App\Http\Controllers\Api;

use App\Application\Api\Queries\CurrentOffersQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CurrentOfferController extends Controller
{
    public function index(CurrentOffersQueryService $queryService): JsonResponse
    {
        return response()->json(
            ['data' => $queryService->handle()],
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
