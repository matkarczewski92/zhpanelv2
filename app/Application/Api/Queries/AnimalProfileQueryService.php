<?php

namespace App\Application\Api\Queries;

use App\Application\Api\Services\GalleryUrlNormalizer;
use App\Models\Animal;
use App\Models\Litter;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnimalProfileQueryService
{
    public function __construct(
        private readonly GalleryUrlNormalizer $galleryUrlNormalizer
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(string $secretTag): array
    {
        $animal = Animal::query()
            ->with([
                'animalType:id,name',
                'feedings' => fn ($query) => $query
                    ->with('feed:id,name')
                    ->orderByDesc('created_at'),
                'weights' => fn ($query) => $query->orderByDesc('created_at'),
                'molts' => fn ($query) => $query->orderByDesc('created_at'),
                'genotypes' => fn ($query) => $query
                    ->with('category:id,name,gene_code,gene_type'),
                'photos' => fn ($query) => $query->orderByDesc('created_at'),
                'litter:id,litter_code,category,connection_date,laying_date,hatching_date,season,parent_male,parent_female',
            ])
            ->where('secret_tag', $secretTag)
            ->first();

        if (!$animal) {
            throw (new ModelNotFoundException())->setModel(Animal::class, [$secretTag]);
        }

        $relatedLitters = Litter::query()
            ->where('parent_male', $animal->id)
            ->orWhere('parent_female', $animal->id)
            ->orderByDesc('id')
            ->get(['id', 'litter_code', 'category', 'connection_date', 'laying_date', 'hatching_date', 'season', 'parent_male', 'parent_female']);

        if ($animal->litter) {
            $relatedLitters->prepend($animal->litter);
        }

        $litters = $relatedLitters
            ->unique('id')
            ->values()
            ->map(function (Litter $litter): array {
                return [
                    'id' => (int) $litter->id,
                    'litter_code' => $litter->litter_code,
                    'category' => $litter->category !== null ? (int) $litter->category : null,
                    'season' => $litter->season !== null ? (int) $litter->season : null,
                    'connection_date' => $litter->connection_date?->format('Y-m-d'),
                    'laying_date' => $litter->laying_date?->format('Y-m-d'),
                    'hatching_date' => $litter->hatching_date?->format('Y-m-d'),
                    'parent_male' => $litter->parent_male !== null ? (int) $litter->parent_male : null,
                    'parent_female' => $litter->parent_female !== null ? (int) $litter->parent_female : null,
                ];
            })
            ->all();

        return [
            'animal' => [
                'id' => (int) $animal->id,
                'name' => $animal->name,
                'second_name' => $animal->second_name,
                'sex' => $animal->sex !== null ? (int) $animal->sex : null,
                'animal_type' => $animal->animalType ? [
                    'id' => (int) $animal->animalType->id,
                    'name' => $animal->animalType->name,
                ] : null,
                'animal_category_id' => $animal->animal_category_id !== null ? (int) $animal->animal_category_id : null,
                'public_profile_tag' => $animal->public_profile_tag,
                'secret_tag' => $animal->secret_tag,
                'date_of_birth' => $animal->date_of_birth?->format('Y-m-d'),
                'created_at' => $animal->created_at?->toAtomString(),
                'updated_at' => $animal->updated_at?->toAtomString(),
            ],
            'genetics' => $animal->genotypes->map(function ($genotype): array {
                return [
                    'id' => (int) $genotype->id,
                    'type' => $genotype->type,
                    'category' => $genotype->category ? [
                        'id' => (int) $genotype->category->id,
                        'name' => $genotype->category->name,
                        'gene_code' => $genotype->category->gene_code,
                        'gene_type' => $genotype->category->gene_type,
                    ] : null,
                ];
            })->values()->all(),
            'feedings' => $animal->feedings->map(function ($feeding): array {
                return [
                    'id' => (int) $feeding->id,
                    'feed_id' => $feeding->feed_id !== null ? (int) $feeding->feed_id : null,
                    'feed_name' => $feeding->feed?->name,
                    'amount' => $feeding->amount !== null ? (int) $feeding->amount : null,
                    'created_at' => $feeding->created_at?->toAtomString(),
                ];
            })->values()->all(),
            'weights' => $animal->weights->map(function ($weight): array {
                return [
                    'id' => (int) $weight->id,
                    'value' => $weight->value !== null ? (float) $weight->value : null,
                    'created_at' => $weight->created_at?->toAtomString(),
                ];
            })->values()->all(),
            'sheds' => $animal->molts->map(function ($molt): array {
                return [
                    'id' => (int) $molt->id,
                    'created_at' => $molt->created_at?->toAtomString(),
                ];
            })->values()->all(),
            'litters' => $litters,
            'gallery' => $animal->photos->map(function ($photo): array {
                return [
                    'id' => (int) $photo->id,
                    'url' => $this->galleryUrlNormalizer->normalize((string) $photo->url),
                    'is_main' => (int) $photo->main_profil_photo,
                    'banner_position' => (int) $photo->banner_possition,
                    'website' => (int) $photo->webside,
                ];
            })->values()->all(),
        ];
    }
}
