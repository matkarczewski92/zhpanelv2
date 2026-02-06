<?php

namespace App\Http\Controllers\Panel;

use App\Application\Devices\Queries\GetDevicesIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\DevicesIndexRequest;
use Illuminate\View\View;

class DevicesController extends Controller
{
    public function index(DevicesIndexRequest $request, GetDevicesIndexQuery $query): View
    {
        return view('panel.devices.index', [
            'page' => $query->handle($request->validated()),
        ]);
    }
}

