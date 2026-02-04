<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Queries\GetShippingListIndexQuery;
use App\Application\Admin\Services\BuildShippingListPrintDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingListIndexRequest;
use App\Http\Requests\Admin\ShippingListPrintRequest;
use Illuminate\View\View;

class ShippingListController extends Controller
{
    public function index(ShippingListIndexRequest $request, GetShippingListIndexQuery $query): View
    {
        return view('admin.shipping-list.index', [
            'vm' => $query->handle($request->validated()),
        ]);
    }

    public function print(ShippingListPrintRequest $request, BuildShippingListPrintDataService $service): View
    {
        return view('admin.shipping-list.print', [
            'vm' => $service->handle($request->validated()['animal_ids']),
        ]);
    }
}

