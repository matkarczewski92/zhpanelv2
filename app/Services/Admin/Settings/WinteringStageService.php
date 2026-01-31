<?php

namespace App\Services\Admin\Settings;

use App\Models\WinteringStage;

class WinteringStageService
{
    public function store(array $data): WinteringStage
    {
        return WinteringStage::create($data);
    }

    public function update(WinteringStage $stage, array $data): WinteringStage
    {
        $stage->update($data);
        return $stage;
    }

    public function destroy(WinteringStage $stage): array
    {
        $inUse = \App\Models\Wintering::where('stage_id', $stage->id)->exists();
        if ($inUse) {
            return ['type' => 'error', 'message' => 'Etap jest używany w zimowaniu – nie można usunąć.'];
        }
        $stage->delete();
        return ['type' => 'success', 'message' => 'Etap usunięty.'];
    }
}
