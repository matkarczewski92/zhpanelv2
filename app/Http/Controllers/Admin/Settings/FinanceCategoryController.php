<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\FinanceCategoryRequest;
use App\Models\FinanceCategory;
use App\Services\Admin\Settings\FinanceCategoryService;

class FinanceCategoryController extends Controller
{
    public function __construct(private readonly FinanceCategoryService $service)
    {
    }

    public function store(FinanceCategoryRequest $request)
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'finance-categories'])
            ->with('toast', ['type' => 'success', 'message' => 'Kategorie finansowa dodana.']);
    }

    public function update(FinanceCategoryRequest $request, FinanceCategory $category)
    {
        $this->service->update($category, $request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => 'finance-categories'])
            ->with('toast', ['type' => 'success', 'message' => 'Kategorie finansowa zaktualizowana.']);
    }

    public function destroy(FinanceCategory $category)
    {
        $result = $this->service->destroy($category);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'finance-categories'])
            ->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
