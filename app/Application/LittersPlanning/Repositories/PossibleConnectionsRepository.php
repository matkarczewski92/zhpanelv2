<?php

namespace App\Application\LittersPlanning\Repositories;

use App\Models\Animal;
use App\Models\AnimalGenotypeCategory;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PossibleConnectionsRepository
{
    /**
     * @return LengthAwarePaginator<int, object>
     */
    public function paginateEligiblePairs(int $perPage, int $page, string $pageName = 'possible_connections_page'): LengthAwarePaginator
    {
        return $this->eligiblePairsQuery()
            ->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * @return array<int, array{
     *     female_id:int,
     *     female_name:string,
     *     female_type_id:int|null,
     *     male_id:int,
     *     male_name:string,
     *     male_type_id:int|null
     * }>
     */
    public function getEligiblePairsSorted(): array
    {
        return $this->eligiblePairsQuery()
            ->get()
            ->map(function (object $row): array {
                return [
                    'female_id' => (int) $row->female_id,
                    'female_name' => trim((string) $row->female_name),
                    'female_type_id' => $row->female_type_id !== null ? (int) $row->female_type_id : null,
                    'male_id' => (int) $row->male_id,
                    'male_name' => trim((string) $row->male_name),
                    'male_type_id' => $row->male_type_id !== null ? (int) $row->male_type_id : null,
                ];
            })
            ->all();
    }

    /**
     * @param array<int, int> $animalIds
     * @return Collection<int, Animal>
     */
    public function getAnimalsWithGenotypesByIds(array $animalIds): Collection
    {
        if (empty($animalIds)) {
            return collect();
        }

        return Animal::query()
            ->whereIn('id', $animalIds)
            ->with(['genotypes.category'])
            ->get(['id', 'name', 'animal_type_id']);
    }

    /**
     * @return array<int, array{0:string,1:string,2:string}>
     */
    public function getGenotypeDictionary(): array
    {
        return AnimalGenotypeCategory::query()
            ->get(['gene_code', 'name', 'gene_type'])
            ->map(fn (AnimalGenotypeCategory $gene): array => [
                (string) $gene->gene_code,
                (string) $gene->name,
                (string) $gene->gene_type,
            ])
            ->all();
    }

    private function eligiblePairsQuery(): Builder
    {
        $females = $this->eligibleAnimalsBySexSubquery(3);
        $males = $this->eligibleAnimalsBySexSubquery(2);

        return DB::query()
            ->fromSub($females, 'f')
            ->crossJoinSub($males, 'm')
            ->select([
                'f.id as female_id',
                'f.name as female_name',
                'f.animal_type_id as female_type_id',
                'm.id as male_id',
                'm.name as male_name',
                'm.animal_type_id as male_type_id',
            ])
            ->orderByRaw('LOWER(f.name)')
            ->orderBy('f.id')
            ->orderByRaw('LOWER(m.name)')
            ->orderBy('m.id');
    }

    private function eligibleAnimalsBySexSubquery(int $sex): Builder
    {
        $latestWeightIds = DB::table('animal_weights as aw')
            ->selectRaw('aw.animal_id, MAX(aw.id) as latest_weight_id')
            ->groupBy('aw.animal_id');

        return DB::table('animals as a')
            ->joinSub($latestWeightIds, 'lw_latest', function ($join): void {
                $join->on('lw_latest.animal_id', '=', 'a.id');
            })
            ->join('animal_weights as lw', 'lw.id', '=', 'lw_latest.latest_weight_id')
            ->where('a.animal_category_id', 1)
            ->where('a.sex', $sex)
            ->where('lw.value', '>=', 250)
            ->select([
                'a.id',
                'a.name',
                'a.animal_type_id',
            ]);
    }
}

