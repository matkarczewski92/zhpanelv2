<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\TraitRequest;
use App\Models\AnimalGenotypeTrait;
use App\Services\Admin\Settings\TraitService;

class TraitController extends Controller
{
    public function __construct(private readonly TraitService $service)
    {
    }

    public function store(TraitRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Trait dodany.']);
    }

    public function update(TraitRequest $request, AnimalGenotypeTrait $trait)
    {
        $this->service->update($trait, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Trait zaktualizowany.']);
    }

    public function destroy(AnimalGenotypeTrait $trait)
    {
        $this->service->destroy($trait);
        return back()->with('toast', ['type' => 'success', 'message' => 'Trait usunięty.']);
    }
}
