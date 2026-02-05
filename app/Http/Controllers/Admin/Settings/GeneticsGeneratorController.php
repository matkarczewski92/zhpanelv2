<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreGeneratedGenotypesRequest;
use App\Services\Admin\Settings\AdminSettingsService;
use App\Services\Admin\Settings\GeneticsGeneratorService;
use Illuminate\Http\Request;

class GeneticsGeneratorController extends Controller
{
    public function __construct(
        private readonly AdminSettingsService $settingsService,
        private readonly GeneticsGeneratorService $generatorService
    ) {
    }

    public function generate(Request $request)
    {
        $vm = $this->settingsService->getViewModel('genetics-generator');
        $generatedRows = $this->generatorService->buildRowsForAnimals($vm->animalsWithoutGenotypes);
        $selectedAnimalIds = $this->resolveSelectedAnimalIds(
            $request->input('selected_animal_ids', []),
            $vm->animalsWithoutGenotypes->pluck('id')->all()
        );

        return view('admin.settings.index', [
            'vm' => $vm,
            'generatedRows' => $generatedRows,
            'selectedAnimalIds' => $selectedAnimalIds,
        ]);
    }

    public function store(StoreGeneratedGenotypesRequest $request)
    {
        $vm = $this->settingsService->getViewModel('genetics-generator');
        $availableIds = $vm->animalsWithoutGenotypes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedIds = $this->resolveSelectedAnimalIds($request->validated('selected_animal_ids', []), $availableIds);

        if (empty($selectedIds)) {
            return redirect()
                ->route('admin.settings.index', ['tab' => 'genetics-generator'])
                ->with('toast', ['type' => 'error', 'message' => 'Nie zaznaczono żadnych węży do zapisu.']);
        }

        $animalsToStore = $vm->animalsWithoutGenotypes->whereIn('id', $selectedIds)->values();
        $result = $this->generatorService->storeGeneratedForAnimals($animalsToStore);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'genetics-generator'])
            ->with('toast', [
                'type' => 'success',
                'message' => "Zapisano {$result['rows']} genów dla {$result['animals']} węży.",
            ]);
    }

    /**
     * @param mixed $selected
     * @param array<int, int> $availableIds
     * @return array<int, int>
     */
    private function resolveSelectedAnimalIds(mixed $selected, array $availableIds): array
    {
        $allowed = array_fill_keys(array_map('intval', $availableIds), true);
        $resolved = [];

        foreach ((array) $selected as $id) {
            $id = (int) $id;
            if ($id > 0 && isset($allowed[$id])) {
                $resolved[$id] = $id;
            }
        }

        if (!empty($resolved)) {
            return array_values($resolved);
        }

        return array_values(array_map('intval', $availableIds));
    }
}
