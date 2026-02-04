<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentOfferResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'offer_id' => $this->resource['offer_id'] ?? null,
            'animal_id' => $this->resource['animal_id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'sex' => $this->resource['sex'] ?? null,
            'sex_label' => $this->resource['sex_label'] ?? null,
            'price' => $this->resource['price'] ?? null,
            'has_reservation' => $this->resource['has_reservation'] ?? false,
            'date_of_birth' => $this->resource['date_of_birth'] ?? null,
            'main_photo_url' => $this->resource['main_photo_url'] ?? null,
            'public_profile_url' => $this->resource['public_profile_url'] ?? null,
        ];
    }
}
