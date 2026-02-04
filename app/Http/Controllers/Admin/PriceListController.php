<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Queries\GetPriceListIndexQuery;
use App\Application\Admin\Services\BuildPriceListPrintDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceListIndexRequest;
use App\Http\Requests\Admin\PriceListPrintRequest;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(PriceListIndexRequest $request, GetPriceListIndexQuery $query): View
    {
        return view('admin.pricelist.index', [
            'vm' => $query->handle($request->validated()),
        ]);
    }

    public function print(PriceListPrintRequest $request, BuildPriceListPrintDataService $service): View
    {
        return view('admin.pricelist.print', [
            'vm' => $service->handle($request->validated()['animal_ids']),
        ]);
    }
}

