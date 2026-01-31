<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Services\PassportService;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\View\View;

class AnimalPassportController extends Controller
{
    public function show(Animal $animal, PassportService $passportService): View
    {
        $passport = $passportService->buildForAnimals([$animal->id]);

        return view('panel.passports.print', [
            'passports' => $passport,
            'bulk' => false,
        ]);
    }
}
