<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\AddWeightCommand;
use App\Application\Animals\Commands\DeleteWeightCommand;
use App\Application\Animals\Commands\UpdateWeightCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalWeightRequest;
use App\Http\Requests\UpdateAnimalWeightRequest;
use App\Models\Animal;
use App\Models\AnimalWeight;

class AnimalWeightController extends Controller
{
    public function store(StoreAnimalWeightRequest $request, AddWeightCommand $command)
    {
        $weight = $command->handle($request->validated());

        return redirect()
            ->route('panel.animals.show', $weight->animal_id)
            ->with('success', 'Waga dodana.');
    }

    public function update(
        UpdateAnimalWeightRequest $request,
        Animal $animal,
        AnimalWeight $weight,
        UpdateWeightCommand $command
    ) {
        $command->handle(array_merge($request->validated(), [
            'id' => $weight->id,
            'animal_id' => $animal->id,
        ]));

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Waga zaktualizowana.');
    }

    public function destroy(Animal $animal, AnimalWeight $weight, DeleteWeightCommand $command)
    {
        $command->handle($animal->id, $weight->id);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Waga usunięta.');
    }
}
