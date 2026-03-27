<?php

namespace App\Http\Controllers\Panel;

use App\Application\Animals\Commands\RecordQrFeedingCommand;
use App\Application\Animals\Commands\RecordQrMoltCommand;
use App\Application\Animals\Commands\RecordQrWeightCommand;
use App\Application\Animals\Queries\GetQrScannerPageQuery;
use App\Application\Animals\Queries\ResolveQrAnimalQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ResolveQrAnimalRequest;
use App\Http\Requests\Panel\StoreQrFeedingRequest;
use App\Http\Requests\Panel\StoreQrMoltRequest;
use App\Http\Requests\Panel\StoreQrWeightRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class QrScannerController extends Controller
{
    public function index(GetQrScannerPageQuery $query): View
    {
        return view('panel.qr-scanner.index', [
            'page' => $query->handle(),
        ]);
    }

    public function resolve(ResolveQrAnimalRequest $request, ResolveQrAnimalQuery $query): JsonResponse
    {
        $result = $query->handle($request->validated());

        return response()->json($result->toArray(), $result->statusCode());
    }

    public function storeFeeding(StoreQrFeedingRequest $request, RecordQrFeedingCommand $command): JsonResponse
    {
        $result = $command->handle($request->validated());

        return response()->json($result->toArray(), $result->statusCode());
    }

    public function storeWeight(StoreQrWeightRequest $request, RecordQrWeightCommand $command): JsonResponse
    {
        $result = $command->handle($request->validated());

        return response()->json($result->toArray(), $result->statusCode());
    }

    public function storeMolt(StoreQrMoltRequest $request, RecordQrMoltCommand $command): JsonResponse
    {
        $result = $command->handle($request->validated());

        return response()->json($result->toArray(), $result->statusCode());
    }
}
