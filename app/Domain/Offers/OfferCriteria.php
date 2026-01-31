<?php

namespace App\Domain\Offers;

use Illuminate\Database\Eloquent\Builder;

class OfferCriteria
{
    public ?int $animalId = null;
    public ?int $animalTypeId = null;
    public ?int $animalCategoryId = null;
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public ?bool $sold = null;
    public ?string $createdFrom = null;
    public ?string $createdTo = null;

    public function apply(Builder $query): Builder
    {
        $query->when($this->animalId, function (Builder $query, int $animalId): Builder {
            return $query->where('animal_id', $animalId);
        });

        $query->when($this->animalTypeId, function (Builder $query, int $animalTypeId): Builder {
            return $query->whereHas('animal', function (Builder $query) use ($animalTypeId): Builder {
                return $query->where('animal_type_id', $animalTypeId);
            });
        });

        $query->when($this->animalCategoryId, function (Builder $query, int $animalCategoryId): Builder {
            return $query->whereHas('animal', function (Builder $query) use ($animalCategoryId): Builder {
                return $query->where('animal_category_id', $animalCategoryId);
            });
        });

        if ($this->minPrice !== null) {
            $query->where('price', '>=', $this->minPrice);
        }

        if ($this->maxPrice !== null) {
            $query->where('price', '<=', $this->maxPrice);
        }

        if ($this->sold === true) {
            $query->whereNotNull('sold_date');
        }

        if ($this->sold === false) {
            $query->whereNull('sold_date');
        }

        $query->when($this->createdFrom, function (Builder $query, string $createdFrom): Builder {
            return $query->whereDate('created_at', '>=', $createdFrom);
        });

        $query->when($this->createdTo, function (Builder $query, string $createdTo): Builder {
            return $query->whereDate('created_at', '<=', $createdTo);
        });

        return $query;
    }
}
