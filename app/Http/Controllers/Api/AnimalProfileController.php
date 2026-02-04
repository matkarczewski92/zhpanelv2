<?php

namespace App\Http\Controllers\Api;

use App\Application\Api\Queries\AnimalProfileQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShowAnimalProfileRequest;
use App\Http\Resources\Api\AnimalProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnimalProfileController extends Controller
{
    public function show(ShowAnimalProfileRequest $request, AnimalProfileQueryService $queryService): JsonResponse
    {
        try {
            $payload = $queryService->handle((string) $request->validated('secret_tag'));
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Nie znaleziono zwierzecia dla podanego secret_tag.',
            ], 404);
        }

        return (new AnimalProfileResource($payload))
            ->response()
            ->setStatusCode(200);
    }
}
