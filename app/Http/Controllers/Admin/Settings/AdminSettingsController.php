<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Admin\Settings\AdminSettingsService;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(private readonly AdminSettingsService $service)
    {
    }

    public function __invoke(Request $request)
    {
        $vm = $this->service->getViewModel($request->query('tab'));

        return view('admin.settings.index', ['vm' => $vm]);
    }
}