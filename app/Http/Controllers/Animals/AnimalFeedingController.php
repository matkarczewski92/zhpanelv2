<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\DeleteFeedingCommand;
use App\Application\Animals\Commands\RecordFeedingCommand;
use App\Application\Animals\Commands\UpdateFeedingCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalFeedingRequest;
use App\Http\Requests\UpdateAnimalFeedingRequest;
use App\Models\Animal;
use App\Models\AnimalFeeding;

class AnimalFeedingController extends Controller
{
    public function store(StoreAnimalFeedingRequest $request, RecordFeedingCommand $command)
    {
        $feeding = $command->handle($request->validated());

        return redirect()
            ->route('panel.animals.show', $feeding->animal_id)
            ->with('success', 'Karmienie dodane.');
    }

    public function update(
        UpdateAnimalFeedingRequest $request,
        Animal $animal,
        AnimalFeeding $feeding,
        UpdateFeedingCommand $command
    ) {
        $command->handle(array_merge($request->validated(), [
            'id' => $feeding->id,
            'animal_id' => $animal->id,
        ]));

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Karmienie zaktualizowane.');
    }

    public function destroy(Animal $animal, AnimalFeeding $feeding, DeleteFeedingCommand $command)
    {
        $command->handle($animal->id, $feeding->id);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Karmienie usunięte.');
    }
}
