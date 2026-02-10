<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Services\PhotoGalleryService;
use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalPhotoGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnimalPhotoController extends Controller
{
    private function galleryRedirect(Animal $animal): RedirectResponse
    {
        return redirect()
            ->route('panel.animals.show', $animal->id)
            ->with('open_modal', 'gallery');
    }

    public function store(Request $request, Animal $animal, PhotoGalleryService $service): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        try {
            $service->upload($animal, $data['photo']);

            return $this->galleryRedirect($animal)->with('toast', [
                'type' => 'success',
                'message' => 'Zdjecie zapisane.',
            ]);
        } catch (\Throwable) {
            return $this->galleryRedirect($animal)->with('toast', [
                'type' => 'error',
                'message' => 'Nie udalo sie przetworzyc zdjecia.',
            ]);
        }
    }

    public function destroy(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->delete($animal, $photo);

        return $this->galleryRedirect($animal)->with('toast', [
            'type' => 'success',
            'message' => 'Zdjecie usuniete.',
        ]);
    }

    public function setMain(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->setMain($animal, $photo);

        return $this->galleryRedirect($animal)->with('toast', [
            'type' => 'success',
            'message' => 'Ustawiono jako glowne.',
        ]);
    }

    public function toggleWebsite(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->toggleWebsite($animal, $photo);

        return $this->galleryRedirect($animal)->with('toast', [
            'type' => 'success',
            'message' => $photo->webside ? 'Zdjecie widoczne na stronie.' : 'Zdjecie ukryte na stronie.',
        ]);
    }
}
