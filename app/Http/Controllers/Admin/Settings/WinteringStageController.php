<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\WinteringStageRequest;
use App\Models\WinteringStage;
use App\Services\Admin\Settings\WinteringStageService;

class WinteringStageController extends Controller
{
    public function __construct(private readonly WinteringStageService $service)
    {
    }

    public function store(WinteringStageRequest $request)
    {
        $this->service->store($request->validated());
        return redirect()
            ->route('admin.settings.index', ['tab' => 'winter'])
            ->with('toast', ['type' => 'success', 'message' => 'Etap dodany.']);
    }

    public function update(WinteringStageRequest $request, WinteringStage $stage)
    {
        $this->service->update($stage, $request->validated());
        return redirect()
            ->route('admin.settings.index', ['tab' => 'winter'])
            ->with('toast', ['type' => 'success', 'message' => 'Etap zaktualizowany.']);
    }

    public function destroy(WinteringStage $stage)
    {
        $result = $this->service->destroy($stage);
        return redirect()
            ->route('admin.settings.index', ['tab' => 'winter'])
            ->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
