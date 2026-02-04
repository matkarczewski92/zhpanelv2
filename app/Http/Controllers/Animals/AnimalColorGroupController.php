<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\SyncAnimalColorGroupsCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncAnimalColorGroupsRequest;
use App\Models\Animal;

class AnimalColorGroupController extends Controller
{
    public function __invoke(
        SyncAnimalColorGroupsRequest $request,
        Animal $animal,
        SyncAnimalColorGroupsCommand $command
    ) {
        $validated = $request->validated();
        $ids = $validated['color_group_ids'] ?? [];

        $command->handle($animal->id, $ids);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('toast', ['type' => 'success', 'message' => 'Grupy kolorystyczne zapisane.']);
    }
}

