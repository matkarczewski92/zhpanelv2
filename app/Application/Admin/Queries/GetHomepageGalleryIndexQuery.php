<?php

namespace App\Application\Admin\Queries;

use App\Domain\Admin\Gallery\AdminHomepageGalleryRepositoryInterface;
use App\Models\AnimalPhotoGallery;

class GetHomepageGalleryIndexQuery
{
    public function __construct(
        private readonly AdminHomepageGalleryRepositoryInterface $repository
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $photos = $this->repository
            ->paginateFeatured(24)
            ->through(function (AnimalPhotoGallery $photo): array {
                $animal = $photo->animal;

                return [
                    'id' => (int) $photo->id,
                    'image_url' => $this->photoUrl((string) $photo->url),
                    'animal_id' => (int) $photo->animal_id,
                    'animal_name' => $this->formatAnimalName(
                        $animal?->name,
                        $animal?->second_name
                    ),
                    'public_tag' => $animal?->public_profile_tag ?: null,
                    'type_name' => trim((string) ($animal?->animalType?->name ?? '')),
                    'profile_url' => route('panel.animals.show', $photo->animal_id),
                    'remove_url' => route('admin.homepage-gallery.remove', $photo->id),
                    'updated_at' => optional($photo->updated_at)->format('Y-m-d H:i'),
                ];
            });

        return [
            'photos' => $photos,
        ];
    }

    private function photoUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return asset($url);
    }

    private function formatAnimalName(?string $name, ?string $secondName): string
    {
        $main = trim(strip_tags((string) $name, '<b><i><u><strong><em><br>'));
        $second = trim(strip_tags((string) $secondName));
        $label = trim($second !== '' ? e($second) . ' ' . $main : $main);

        return $label !== '' ? $label : '-';
    }
}
