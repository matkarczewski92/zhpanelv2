<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Admin\Settings\AdminSettingsService;
use App\Services\Admin\Settings\PortalUpdateService;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly AdminSettingsService $service,
        private readonly PortalUpdateService $portalUpdateService
    ) {
    }

    public function __invoke(Request $request)
    {
        $vm = $this->service->getViewModel($request->query('tab'));
        $updatePanel = $this->portalUpdateService->getPanelData(
            $request->session()->get('admin_update_last_check'),
            $request->session()->get('admin_update_last_run')
        );

        return view('admin.settings.index', [
            'vm' => $vm,
            'importPreview' => $request->session()->get('admin_settings_import_preview'),
            'updatePanel' => $updatePanel,
        ]);
    }
}
