<?php

namespace App\Http\Controllers\Animals;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\RedirectResponse;

class AnimalPublicVisibilityController extends Controller
{
    public function __invoke(Animal $animal): RedirectResponse
    {
        $animal->public_profile = $animal->public_profile ? 0 : 1;
        $animal->save();

        return redirect()
            ->route('panel.animals.show', $animal->id)
            ->with('toast', [
                'type' => 'success',
                'message' => $animal->public_profile ? 'Profil publiczny w??czony.' : 'Profil publiczny wy??czony.',
            ]);
    }
}



