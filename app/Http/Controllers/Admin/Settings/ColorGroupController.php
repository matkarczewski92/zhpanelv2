<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ColorGroupRequest;
use App\Models\ColorGroup;
use App\Services\Admin\Settings\ColorGroupService;

class ColorGroupController extends Controller
{
    public function __construct(private readonly ColorGroupService $service)
    {
    }

    public function store(ColorGroupRequest $request)
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'color-groups'])
            ->with('toast', ['type' => 'success', 'message' => 'Grupa kolorystyczna dodana.']);
    }

    public function update(ColorGroupRequest $request, ColorGroup $colorGroup)
    {
        $this->service->update($colorGroup, $request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'color-groups'])
            ->with('toast', ['type' => 'success', 'message' => 'Grupa kolorystyczna zaktualizowana.']);
    }

    public function destroy(ColorGroup $colorGroup)
    {
        $result = $this->service->destroy($colorGroup);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'color-groups'])
            ->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}

