<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AnimalCategoryRequest;
use App\Services\Admin\Settings\AnimalCategoryService;
use App\Models\AnimalCategory;

class AnimalCategoryController extends Controller
{
    public function __construct(private readonly AnimalCategoryService $service)
    {
    }

    public function store(AnimalCategoryRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Kategoria dodana.']);
    }

    public function update(AnimalCategoryRequest $request, AnimalCategory $category)
    {
        $this->service->update($category, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Kategoria zaktualizowana.']);
    }

    public function destroy(AnimalCategory $category)
    {
        $result = $this->service->destroy($category);
        return back()->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
