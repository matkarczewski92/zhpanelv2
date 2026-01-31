<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\GenotypeCategoryRequest;
use App\Models\AnimalGenotypeCategory;
use App\Services\Admin\Settings\GenotypeCategoryService;

class GenotypeCategoryController extends Controller
{
    public function __construct(private readonly GenotypeCategoryService $service)
    {
    }

    public function store(GenotypeCategoryRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Genotyp dodany.']);
    }

    public function update(GenotypeCategoryRequest $request, AnimalGenotypeCategory $category)
    {
        $this->service->update($category, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Genotyp zaktualizowany.']);
    }

    public function destroy(AnimalGenotypeCategory $category)
    {
        $result = $this->service->destroy($category);
        return back()->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
