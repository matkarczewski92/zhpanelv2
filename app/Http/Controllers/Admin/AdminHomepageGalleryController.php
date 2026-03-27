<?php

namespace App\Http\Controllers\Admin;

use App\Application\Admin\Commands\RemoveHomepageGalleryPhotoCommand;
use App\Application\Admin\Queries\GetHomepageGalleryIndexQuery;
use App\Http\Controllers\Controller;
use App\Models\AnimalPhotoGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminHomepageGalleryController extends Controller
{
    public function index(GetHomepageGalleryIndexQuery $query): View
    {
        return view('admin.homepage-gallery.index', [
            'page' => $query->handle(),
        ]);
    }

    public function remove(AnimalPhotoGallery $photo, RemoveHomepageGalleryPhotoCommand $command): RedirectResponse
    {
        $result = $command->handle((int) $photo->id);

        return redirect()
            ->route('admin.homepage-gallery.index')
            ->with('toast', [
                'type' => ($result['status'] ?? null) === 'ok' ? 'success' : 'warning',
                'message' => $result['message'] ?? 'Nie udalo sie zaktualizowac zdjecia.',
            ]);
    }
}
