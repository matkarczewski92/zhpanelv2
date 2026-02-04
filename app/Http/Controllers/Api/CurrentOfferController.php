<?php

namespace App\Http\Controllers\Api;

use App\Application\Api\Queries\CurrentOffersQueryService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CurrentOfferResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrentOfferController extends Controller
{
    public function index(CurrentOffersQueryService $queryService): AnonymousResourceCollection
    {
        return CurrentOfferResource::collection($queryService->handle());
    }
}
