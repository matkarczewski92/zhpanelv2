<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\SystemConfigRequest;
use App\Models\SystemConfig;
use App\Services\Admin\Settings\SystemConfigService;

class SystemConfigController extends Controller
{
    public function __construct(private readonly SystemConfigService $service)
    {
    }

    public function store(SystemConfigRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Ustawienie dodane.']);
    }

    public function update(SystemConfigRequest $request, SystemConfig $config)
    {
        $this->service->update($config, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Ustawienie zaktualizowane.']);
    }

    public function destroy(SystemConfig $config)
    {
        $this->service->destroy($config);
        return back()->with('toast', ['type' => 'success', 'message' => 'Ustawienie usunięte.']);
    }
}
