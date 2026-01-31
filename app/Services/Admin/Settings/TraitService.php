<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use App\Models\AnimalGenotypeTraitsDictionary;
use Illuminate\Support\Facades\DB;

class TraitService
{
    public function store(array $data): AnimalGenotypeTrait
    {
        return DB::transaction(function () use ($data) {
            /** @var AnimalGenotypeTrait $trait */
            $trait = AnimalGenotypeTrait::create([
                'name' => $data['name'],
                'number_of_traits' => $data['gene_ids'] ? count($data['gene_ids']) : 0,
            ]);
            $this->syncGenes($trait, $data['gene_ids'] ?? []);
            return $trait;
        });
    }

    public function update(AnimalGenotypeTrait $trait, array $data): AnimalGenotypeTrait
    {
        return DB::transaction(function () use ($trait, $data) {
            $trait->update([
                'name' => $data['name'],
                'number_of_traits' => $data['gene_ids'] ? count($data['gene_ids']) : 0,
            ]);
            $this->syncGenes($trait, $data['gene_ids'] ?? []);
            return $trait;
        });
    }

    public function destroy(AnimalGenotypeTrait $trait): void
    {
        DB::transaction(function () use ($trait) {
            AnimalGenotypeTraitsDictionary::where('trait_id', $trait->id)->delete();
            $trait->delete();
        });
    }

    protected function syncGenes(AnimalGenotypeTrait $trait, array $geneIds): void
    {
        AnimalGenotypeTraitsDictionary::where('trait_id', $trait->id)->delete();
        $unique = collect($geneIds)->unique()->filter()->values();
        if ($unique->count() >= 1) {
            $rows = $unique->map(fn ($id) => [
                'trait_id' => $trait->id,
                'category_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
            AnimalGenotypeTraitsDictionary::insert($rows);
        }
    }
}
