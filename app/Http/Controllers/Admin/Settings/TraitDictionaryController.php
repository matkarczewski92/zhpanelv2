<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AnimalGenotypeTrait;
use App\Models\AnimalGenotypeTraitsDictionary;
use App\Services\Admin\Settings\TraitDictionaryService;
use Illuminate\Http\Request;

class TraitDictionaryController extends Controller
{
    public function __construct(private readonly TraitDictionaryService $service)
    {
    }

    public function store(Request $request, AnimalGenotypeTrait $trait)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:animal_genotype_category,id'],
        ]);

        $result = $this->service->addGene($trait, (int) $validated['category_id']);

        return back()->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }

    public function destroy(AnimalGenotypeTrait $trait, AnimalGenotypeTraitsDictionary $dictionary)
    {
        $this->service->removeGene($trait, $dictionary);
        return back()->with('toast', ['type' => 'success', 'message' => 'Gen usunięty z traitu.']);
    }
}
