<?php

namespace App\Application\Animals\Services;

use App\Models\Animal;
use App\Models\AnimalPhotoGallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PhotoGalleryService
{
    public function __construct(
        private readonly ImageOptimizationService $images
    ) {
    }

    public function upload(Animal $animal, UploadedFile $file): void
    {
        $dir = 'Image/Animals/' . $animal->id;
        $optimized = $this->images->optimizeAndStoreInPublicPath($file, $dir);
        $path = $optimized['path'];

        $isFirst = !AnimalPhotoGallery::where('animal_id', $animal->id)->exists();

        AnimalPhotoGallery::create([
            'animal_id' => $animal->id,
            'url' => $path,
            'main_profil_photo' => $isFirst ? 1 : 0,
            'banner_possition' => 50,
            'webside' => 0,
        ]);
    }

    public function delete(Animal $animal, AnimalPhotoGallery $photo): void
    {
        $this->authorizeOwnership($animal, $photo);

        $abs = public_path($photo->url);
        if ($photo->url && file_exists($abs)) {
            @unlink($abs);
        }

        $photo->delete();
    }

    public function setMain(Animal $animal, AnimalPhotoGallery $photo): void
    {
        $this->authorizeOwnership($animal, $photo);

        DB::transaction(function () use ($animal, $photo): void {
            AnimalPhotoGallery::where('animal_id', $animal->id)->update(['main_profil_photo' => 0]);
            $photo->main_profil_photo = 1;
            $photo->save();
        });
    }

    public function toggleWebsite(Animal $animal, AnimalPhotoGallery $photo): void
    {
        $this->authorizeOwnership($animal, $photo);
        $photo->webside = $photo->webside ? 0 : 1;
        $photo->save();
    }

    private function authorizeOwnership(Animal $animal, AnimalPhotoGallery $photo): void
    {
        if ($photo->animal_id !== $animal->id) {
            abort(403);
        }
    }
}
