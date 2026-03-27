<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Admin\Gallery\AdminHomepageGalleryRepositoryInterface;
use App\Models\AnimalPhotoGallery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAdminHomepageGalleryRepository implements AdminHomepageGalleryRepositoryInterface
{
    public function paginateFeatured(int $perPage): LengthAwarePaginator
    {
        return AnimalPhotoGallery::query()
            ->with([
                'animal:id,name,second_name,public_profile_tag,animal_type_id',
                'animal.animalType:id,name',
            ])
            ->where('webside', 1)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function removeFromHomepage(int $photoId): bool
    {
        return AnimalPhotoGallery::query()
            ->whereKey($photoId)
            ->where('webside', 1)
            ->update([
                'webside' => 0,
                'updated_at' => now(),
            ]) > 0;
    }
}
