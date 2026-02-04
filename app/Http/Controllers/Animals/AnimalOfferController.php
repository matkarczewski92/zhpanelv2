<?php

namespace App\Http\Controllers\Animals;

use App\Application\Animals\Services\AnimalOfferService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\AnimalOfferUpdateRequest;
use App\Models\Animal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnimalOfferController extends Controller
{
    public function update(AnimalOfferUpdateRequest $request, AnimalOfferService $service, Animal $animal): RedirectResponse
    {
        $service->updateOffer($animal, $request->validated());

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta zostala zapisana.');
    }

    public function destroyReservation(Request $request, AnimalOfferService $service, Animal $animal): RedirectResponse
    {
        $service->deleteReservation($animal);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Rezerwacja zostala usunieta.');
    }

    public function destroy(Request $request, AnimalOfferService $service, Animal $animal): RedirectResponse
    {
        $service->deleteOffer($animal);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta zostala usunieta.');
    }

    public function sell(Request $request, AnimalOfferService $service, Animal $animal): RedirectResponse
    {
        $service->markAsSold($animal);

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta oznaczona jako sprzedana.');
    }

    public function togglePublic(Animal $animal, Request $request, AnimalOfferService $service): RedirectResponse
    {
        $service->togglePublicProfile($animal);

        return redirect()
            ->back()
            ->with('success', 'Widocznosc profilu zostala zaktualizowana.');
    }
}
