<?php

namespace App\Http\Controllers\Panel;

use App\Application\Dashboard\Queries\DashboardQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\DashboardIndexRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardIndexRequest $request, DashboardQueryService $queryService): View
    {
        return view('panel.dashboard.index', [
            'page' => $queryService->handle($request->validated()),
        ]);
    }
}
