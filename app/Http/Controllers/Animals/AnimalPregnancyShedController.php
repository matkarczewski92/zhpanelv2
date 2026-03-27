<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\RecordLitterPregnancyShedCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLitterPregnancyShedRequest;
use App\Models\Animal;
use Illuminate\Http\RedirectResponse;

class AnimalPregnancyShedController extends Controller
{
    public function store(
        StoreLitterPregnancyShedRequest $request,
        Animal $animal,
        RecordLitterPregnancyShedCommand $command
    ): RedirectResponse {
        $validated = $request->validated();

        $command->handle($validated);

        $routeParams = ['animal' => $animal->id];
        if (!empty($validated['pregnancy_season'])) {
            $routeParams['pregnancy_season'] = $validated['pregnancy_season'];
        }

        return redirect()
            ->route('panel.animals.show', $routeParams)
            ->with('toast', ['type' => 'success', 'message' => 'Dodano wylinke ciazowa.']);
    }
}
