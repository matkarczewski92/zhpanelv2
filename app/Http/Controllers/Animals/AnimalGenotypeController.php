<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Commands\AddGenotypeCommand;
use App\Application\Animals\Commands\DeleteGenotypeCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalGenotypeRequest;
use App\Models\Animal;
use App\Models\AnimalGenotype;
use App\Models\AnimalGenotypeCategory;

class AnimalGenotypeController extends Controller
{
    public function store(StoreAnimalGenotypeRequest $request, AddGenotypeCommand $command)
    {
        $genotype = $command->handle($request->validated());

        if ($request->expectsJson()) {
            $chipsHtml = $this->chipsHtml($genotype->animal_id);

            return response()->json([
                'message' => 'Genotyp dodany.',
                'chips_html' => $chipsHtml,
            ]);
        }

        return redirect()
            ->route('panel.animals.show', [$genotype->animal_id, '#genetyka'])
            ->with('success', 'Genotyp dodany.');
    }

    public function destroy(Animal $animal, AnimalGenotype $genotype, DeleteGenotypeCommand $command)
    {
        $command->handle($animal->id, $genotype->id);

        if (request()->expectsJson()) {
            $chipsHtml = $this->chipsHtml($animal->id);

            return response()->json([
                'message' => 'Genotyp usunięty.',
                'chips_html' => $chipsHtml,
            ]);
        }

        return redirect()
            ->route('panel.animals.show', [$animal, '#genetyka'])
            ->with('success', 'Genotyp usunięty.');
    }

    private function chipsHtml(int $animalId): string
    {
        $animal = Animal::with(['genotypes.category'])->findOrFail($animalId);

        $typeOptions = [
            ['code' => 'v', 'label' => 'v-homozygota'],
            ['code' => 'h', 'label' => 'h-heterozygota'],
            ['code' => 'p', 'label' => 'p-poshet'],
        ];
        $typeMap = collect($typeOptions)->keyBy('code');
        $order = ['v' => 0, 'h' => 1, 'p' => 2];

        $chips = $animal->genotypes
            ->sortBy(fn ($g) => $order[$g->type] ?? 99)
            ->map(function ($genotype) use ($typeMap) {
                $typeLabel = $typeMap[$genotype->type]['label'] ?? $genotype->type;

                return [
                    'id' => $genotype->id,
                    'label' => $genotype->category?->name ?? '-',
                    'type_code' => $genotype->type,
                    'type_label' => $typeLabel,
                    'delete_url' => route('panel.animals.genotypes.destroy', [$genotype->animal_id, $genotype->id]),
                ];
            })->values()->all();

        return view('panel.animals.partials.genotype-chips', [
            'chips' => $chips,
        ])->render();
    }
}
