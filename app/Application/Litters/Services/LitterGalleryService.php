<?php

namespace App\Application\Litters\Services;

use App\Application\Animals\Services\ImageOptimizationService;
use App\Models\Litter;
use App\Models\LitterGallery;
use Illuminate\Http\UploadedFile;

class LitterGalleryService
{
    public function __construct(
        private readonly ImageOptimizationService $images
    ) {
    }

    public function upload(Litter $litter, UploadedFile $file): LitterGallery
    {
        $dir = 'Image/Litters/' . $litter->id;
        $optimized = $this->images->optimizeAndStoreInPublicPath($file, $dir);
        $path = $optimized['path'];

        return LitterGallery::query()->create([
            'litter_id' => $litter->id,
            'url' => $path,
            'main_photo' => 0,
        ]);
    }

    public function delete(Litter $litter, LitterGallery $photo): void
    {
        $this->authorizeOwnership($litter, $photo);

        $abs = public_path($photo->url);
        if ($photo->url && file_exists($abs)) {
            @unlink($abs);
        }

        $photo->delete();
    }

    private function authorizeOwnership(Litter $litter, LitterGallery $photo): void
    {
        if ((int) $photo->litter_id !== (int) $litter->id) {
            abort(403);
        }
    }
}
