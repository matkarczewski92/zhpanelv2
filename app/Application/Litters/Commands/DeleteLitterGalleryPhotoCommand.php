<?php

namespace App\Application\Litters\Commands;

use App\Application\Litters\Services\LitterGalleryService;
use App\Domain\Events\LitterGalleryPhotoDeleted;
use App\Models\Litter;
use App\Models\LitterGallery;
use Illuminate\Support\Facades\DB;

class DeleteLitterGalleryPhotoCommand
{
    public function __construct(
        private readonly LitterGalleryService $galleryService
    ) {
    }

    public function handle(Litter $litter, LitterGallery $photo): void
    {
        DB::transaction(function () use ($litter, $photo): void {
            $photoId = (int) $photo->id;
            $this->galleryService->delete($litter, $photo);

            DB::afterCommit(static function () use ($litter, $photoId): void {
                event(new LitterGalleryPhotoDeleted($litter->id, $photoId));
            });
        });
    }
}

