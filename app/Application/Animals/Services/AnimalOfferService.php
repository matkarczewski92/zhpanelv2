<?php

namespace App\Application\Animals\Services;

use App\Models\Animal;
use App\Models\AnimalOffer;
use App\Models\AnimalOfferReservation;
use App\Models\Finance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnimalOfferService
{
    private const SOLD_ANIMAL_CATEGORY_ID = 3;
    private const SALE_FINANCE_CATEGORY_ID = 3;
    private const FINANCE_TYPE_INCOME = 'i';

    /**
     * @param array<string, mixed> $payload
     */
    public function updateOffer(Animal $animal, array $payload): void
    {
        DB::transaction(function () use ($animal, $payload): void {
            $offer = $this->latestOffer($animal) ?? new AnimalOffer(['animal_id' => $animal->id]);
            $offer->price = $payload['price'];
            $offer->sold_date = $payload['sold_at'] ?? null;
            $offer->save();

            $animal->public_profile = (bool) ($payload['public_profile'] ?? false);
            $animal->save();

            $this->syncReservation($offer, $payload);
        });
    }

    public function deleteOffer(Animal $animal): void
    {
        $offer = $this->latestOffer($animal);
        if (!$offer) {
            return;
        }

        DB::transaction(function () use ($offer): void {
            $offer->reservations()->delete();
            $offer->delete();
        });
    }

    public function deleteReservation(Animal $animal): void
    {
        $offer = $this->latestOffer($animal);
        if (!$offer) {
            return;
        }

        $offer->reservations()->delete();
    }

    public function markAsSold(Animal $animal): void
    {
        $offer = $this->latestOffer($animal);
        if (!$offer) {
            return;
        }

        DB::transaction(function () use ($animal, $offer): void {
            $soldDate = optional($offer->sold_date)->toDateString() ?? now()->toDateString();
            $offer->sold_date = $soldDate;
            $offer->save();

            if ((int) $animal->animal_category_id !== self::SOLD_ANIMAL_CATEGORY_ID) {
                $animal->animal_category_id = self::SOLD_ANIMAL_CATEGORY_ID;
                $animal->save();
            }

            $existingSaleIncome = Finance::query()
                ->where('animal_id', $animal->id)
                ->where('finances_category_id', self::SALE_FINANCE_CATEGORY_ID)
                ->where('type', self::FINANCE_TYPE_INCOME)
                ->exists();

            if ($existingSaleIncome) {
                return;
            }

            $amount = max(0, round((float) ($offer->price ?? 0), 2));
            $titleAnimalName = trim(strip_tags((string) $animal->name));
            $title = 'Sprzedaz zwierzecia: #' . $animal->id . ($titleAnimalName !== '' ? ' ' . $titleAnimalName : '');
            $transactionDate = Carbon::parse($soldDate)->startOfDay();

            $finance = new Finance([
                'finances_category_id' => self::SALE_FINANCE_CATEGORY_ID,
                'amount' => $amount,
                'title' => $title,
                'animal_id' => $animal->id,
                'type' => self::FINANCE_TYPE_INCOME,
            ]);
            $finance->created_at = $transactionDate;
            $finance->updated_at = $transactionDate;
            $finance->save();
        });
    }

    public function togglePublicProfile(Animal $animal): void
    {
        $animal->public_profile = ! $animal->public_profile;
        $animal->save();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncReservation(AnimalOffer $offer, array $payload): void
    {
        $hasReservationInput = filled($payload['reserver_name'] ?? null)
            || filled($payload['deposit_amount'] ?? null)
            || filled($payload['reservation_valid_until'] ?? null)
            || filled($payload['notes'] ?? null);

        $reservation = $offer->reservation;

        if (!$hasReservationInput) {
            $reservation?->delete();
            return;
        }

        if (!$reservation) {
            $reservation = new AnimalOfferReservation(['offer_id' => $offer->id]);
        }

        $reservation->booker = $payload['reserver_name'] ?? null;
        $reservation->deposit = $payload['deposit_amount'] ?? null;
        $reservation->expiration_date = $payload['reservation_valid_until'] ?? null;
        $reservation->adnotations = $payload['notes'] ?? null;
        $reservation->save();
    }

    private function latestOffer(Animal $animal): ?AnimalOffer
    {
        return $animal->offers()->latest('created_at')->first();
    }
}
