<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\DeleteAnimalCommand;
use App\Application\Animals\Commands\RegisterAnimalCommand;
use App\Application\Animals\Commands\UpdateAnimalCommand;
use App\Application\Animals\Queries\GetAnimalFormDataQuery;
use App\Application\Animals\Queries\GetAnimalProfileQuery;
use App\Application\Animals\Queries\GetAnimalsIndexQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index(Request $request, GetAnimalsIndexQuery $query)
    {
        return view('admin.animals.index', $query->handle($request));
    }

    public function create(GetAnimalFormDataQuery $query)
    {
        return view('admin.animals.create', $query->handle());
    }

    public function store(StoreAnimalRequest $request, RegisterAnimalCommand $command)
    {
        $animal = $command->handle($request->validated());

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('toast', ['type' => 'success', 'message' => 'Zwierzę dodane.']);
    }

    public function show(Animal $animal, Request $request, GetAnimalProfileQuery $query)
    {
        $navigationInput = [
            'nav_ids' => (string) $request->query('nav_ids', ''),
            'nav_back' => (string) $request->query('nav_back', ''),
        ];
        $profile = $query->handle($animal->id, $navigationInput);

        return view('panel.animals.show', [
            'profile' => $profile,
            'animalNav' => $profile->navigation,
        ]);
    }

    public function edit(Animal $animal, GetAnimalFormDataQuery $query)
    {
        return view('admin.animals.edit', array_merge($query->handle(), [
            'animal' => $animal,
        ]));
    }

    public function update(UpdateAnimalRequest $request, Animal $animal, UpdateAnimalCommand $command)
    {
        $updated = $command->handle(array_merge($request->validated(), [
            'id' => $animal->id,
        ]));

        return redirect()
            ->route('panel.animals.show', $updated)
            ->with('toast', ['type' => 'success', 'message' => 'Zwierzę zaktualizowane.']);
    }

    public function destroy(Animal $animal, DeleteAnimalCommand $command)
    {
        $result = $command->handle($animal);

        if ($result['deleted']) {
            return redirect()
                ->route('panel.animals.index')
                ->with('toast', ['type' => 'success', 'message' => $result['message']]);
        }

        return redirect()
            ->back()
            ->with('toast', ['type' => $result['soft'] ? 'success' : 'danger', 'message' => $result['message']]);
    }
}
