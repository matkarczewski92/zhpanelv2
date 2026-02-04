<?php

namespace App\Application\Litters\Commands;

use App\Application\Litters\Services\LitterGalleryService;
use App\Domain\Events\LitterGalleryPhotoAdded;
use App\Models\Litter;
use App\Models\LitterGallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AddLitterGalleryPhotoCommand
{
    public function __construct(
        private readonly LitterGalleryService $galleryService
    ) {
    }

    public function handle(Litter $litter, UploadedFile $photo): LitterGallery
    {
        return DB::transaction(function () use ($litter, $photo): LitterGallery {
            $created = $this->galleryService->upload($litter, $photo);

            DB::afterCommit(static function () use ($litter, $created): void {
                event(new LitterGalleryPhotoAdded($litter->id, $created->id));
            });

            return $created;
        });
    }
}

