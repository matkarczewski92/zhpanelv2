<?php

namespace App\Http\Controllers\Animals;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\AnimalOfferReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnimalOfferController extends Controller
{
    public function store(Request $request, Animal $animal): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'sold_at' => ['nullable', 'date'],
            'public_profile' => ['nullable', 'boolean'],
            'reserver_name' => ['nullable', 'string', 'max:255'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'reservation_valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($animal, $validated): void {
            /** @var AnimalOffer|null $offer */
            $offer = $animal->offers()->latest('created_at')->first();
            if (!$offer) {
                $offer = new AnimalOffer(['animal_id' => $animal->id]);
            }

            $offer->price = $validated['price'];
            $offer->sold_date = $validated['sold_at'] ?? null;
            $offer->save();

            // public profile toggle lives on animal record
            $animal->public_profile = (bool) ($validated['public_profile'] ?? false);
            $animal->save();

            $reservationInputPresent = filled($validated['reserver_name'] ?? null)
                || filled($validated['deposit_amount'] ?? null)
                || filled($validated['reservation_valid_until'] ?? null)
                || filled($validated['notes'] ?? null);

            /** @var AnimalOfferReservation|null $reservation */
            $reservation = $offer->reservations()->first();

            if ($reservationInputPresent) {
                if (!$reservation) {
                    $reservation = new AnimalOfferReservation(['offer_id' => $offer->id]);
                }

                $reservation->booker = $validated['reserver_name'] ?? null;
                $reservation->deposit = $validated['deposit_amount'] ?? null;
                $reservation->expiration_date = $validated['reservation_valid_until'] ?? null;
                $reservation->adnotations = $validated['notes'] ?? null;
                $reservation->save();
            } elseif ($reservation) {
                $reservation->delete();
            }
        });

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta została zapisana.');
    }

    public function destroyReservation(Animal $animal): RedirectResponse
    {
        $offer = $animal->offers()->latest('created_at')->first();
        if ($offer) {
            $offer->reservations()->delete();
        }

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Rezerwacja została usunięta.');
    }

    public function destroy(Animal $animal): RedirectResponse
    {
        $offer = $animal->offers()->latest('created_at')->first();
        if ($offer) {
            $offer->delete();
        }

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta została usunięta.');
    }

    public function sell(Animal $animal): RedirectResponse
    {
        $offer = $animal->offers()->latest('created_at')->first();
        if ($offer) {
            $offer->sold_date = now()->toDateString();
            $offer->save();
        }

        return redirect()
            ->route('panel.animals.show', $animal)
            ->with('success', 'Oferta oznaczona jako sprzedana.');
    }

    public function togglePublic(Animal $animal, Request $request): RedirectResponse
    {
        $animal->public_profile = ! $animal->public_profile;
        $animal->save();

        return redirect()
            ->back()
            ->with('success', 'Widoczność profilu została zaktualizowana.');
    }
}
