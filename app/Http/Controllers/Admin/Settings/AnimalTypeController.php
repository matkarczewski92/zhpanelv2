<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AnimalTypeRequest;
use App\Models\AnimalType;
use App\Services\Admin\Settings\AnimalTypeService;

class AnimalTypeController extends Controller
{
    public function __construct(private readonly AnimalTypeService $service)
    {
    }

    public function store(AnimalTypeRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Typ dodany.']);
    }

    public function update(AnimalTypeRequest $request, AnimalType $type)
    {
        $this->service->update($type, $request->validated());
        return back()->with('toast', ['type' => 'success', 'message' => 'Typ zaktualizowany.']);
    }

    public function destroy(AnimalType $type)
    {
        $result = $this->service->destroy($type);
        return back()->with('toast', ['type' => $result['type'], 'message' => $result['message']]);
    }
}
