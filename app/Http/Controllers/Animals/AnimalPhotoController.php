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
    public function store(Request $request, Animal $animal, PhotoGalleryService $service): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        try {
            $service->upload($animal, $data['photo']);
            return redirect()->route('panel.animals.show', $animal->id)->with('toast', [
                'type' => 'success',
                'message' => 'Zdjęcie zapisane.',
            ])->withFragment('galleryModal');
        } catch (\Throwable $e) {
            return redirect()->route('panel.animals.show', $animal->id)->with('toast', [
                'type' => 'error',
                'message' => 'Nie udało się przetworzyć zdjęcia.',
            ])->withFragment('galleryModal');
        }
    }

    public function destroy(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->delete($animal, $photo);

        return redirect()->route('panel.animals.show', $animal->id)->with('toast', [
            'type' => 'success',
            'message' => 'Zdjęcie usunięte.',
        ])->withFragment('gallery');
    }

    public function setMain(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->setMain($animal, $photo);

        return redirect()->route('panel.animals.show', $animal->id)->with('toast', [
            'type' => 'success',
            'message' => 'Ustawiono jako główne.',
        ])->withFragment('gallery');
    }

    public function toggleWebsite(Animal $animal, AnimalPhotoGallery $photo, PhotoGalleryService $service): RedirectResponse
    {
        $service->toggleWebsite($animal, $photo);

        return redirect()->route('panel.animals.show', $animal->id)->with('toast', [
            'type' => 'success',
            'message' => $photo->webside ? 'Zdjęcie widoczne na stronie.' : 'Zdjęcie ukryte na stronie.',
        ])->withFragment('gallery');
    }
}
