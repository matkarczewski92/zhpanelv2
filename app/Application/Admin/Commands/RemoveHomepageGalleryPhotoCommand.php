<?php

namespace App\Application\Admin\Commands;

use App\Domain\Admin\Gallery\AdminHomepageGalleryRepositoryInterface;

class RemoveHomepageGalleryPhotoCommand
{
    public function __construct(
        private readonly AdminHomepageGalleryRepositoryInterface $repository
    ) {
    }

    /**
     * @return array{status:string,message:string}
     */
    public function handle(int $photoId): array
    {
        $removed = $this->repository->removeFromHomepage($photoId);

        return [
            'status' => $removed ? 'ok' : 'warning',
            'message' => $removed
                ? 'Zdjecie zostalo odznaczone z galerii glownej.'
                : 'To zdjecie nie bylo juz oznaczone w galerii glownej.',
        ];
    }
}
