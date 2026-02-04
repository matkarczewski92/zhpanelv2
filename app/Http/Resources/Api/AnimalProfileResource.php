<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalProfileResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'animal' => $this->resource['animal'] ?? [],
            'genetics' => $this->resource['genetics'] ?? [],
            'feedings' => $this->resource['feedings'] ?? [],
            'weights' => $this->resource['weights'] ?? [],
            'sheds' => $this->resource['sheds'] ?? [],
            'litters' => $this->resource['litters'] ?? [],
            'gallery' => $this->resource['gallery'] ?? [],
        ];
    }
}
