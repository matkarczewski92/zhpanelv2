<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\DeleteMoltCommand;
use App\Application\Animals\Commands\RecordMoltCommand;
use App\Application\Animals\Commands\UpdateMoltCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalMoltRequest;
use App\Http\Requests\UpdateAnimalMoltRequest;
use App\Models\Animal;
use App\Models\AnimalMolt;

class AnimalMoltController extends Controller
{
    public function store(StoreAnimalMoltRequest $request, RecordMoltCommand $command)
    {
        $molt = $command->handle($request->validated());

        return redirect()
            ->route('panel.animals.show', $molt->animal_id)
            ->with('success', 'Wylinka dodana.');
    }

    public function update(
        UpdateAnimalMoltRequest $request,
        Animal $animal,
        AnimalMolt $molt,
        UpdateMoltCommand $command
    ) {
        $command->handle(array_merge($request->validated(), [
            'id' => $molt->id,
            'animal_id' => $animal->id,
        ]));

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Wylinka zaktualizowana.');
    }

    public function destroy(Animal $animal, AnimalMolt $molt, DeleteMoltCommand $command)
    {
        $command->handle($animal->id, $molt->id);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Wylinka usunięta.');
    }
}
