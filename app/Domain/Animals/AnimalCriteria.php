<?php

namespace App\Domain\Animals;

use Illuminate\Database\Eloquent\Builder;

class AnimalCriteria
{
    public ?array $ids = null;
    public ?int $typeId = null;
    public ?int $categoryId = null;
    public ?int $feedId = null;
    public ?int $litterId = null;
    public ?int $sex = null;
    public ?int $publicProfile = null;
    public ?string $search = null;

    public function apply(Builder $query): Builder
    {
        $query->when($this->ids, function (Builder $query, array $ids): Builder {
            return $query->whereIn('id', $ids);
        });

        $query->when($this->typeId, function (Builder $query, int $typeId): Builder {
            return $query->where('animal_type_id', $typeId);
        });

        $query->when($this->categoryId, function (Builder $query, int $categoryId): Builder {
            return $query->where('animal_category_id', $categoryId);
        });

        $query->when($this->feedId, function (Builder $query, int $feedId): Builder {
            return $query->where('feed_id', $feedId);
        });

        $query->when($this->litterId, function (Builder $query, int $litterId): Builder {
            return $query->where('litter_id', $litterId);
        });

        if ($this->sex !== null) {
            $query->where('sex', $this->sex);
        }

        if ($this->publicProfile !== null) {
            $query->where('public_profile', $this->publicProfile);
        }

        $query->when($this->search, function (Builder $query, string $search): Builder {
            $like = '%' . $search . '%';

            return $query->where(function (Builder $query) use ($like): Builder {
                return $query
                    ->where('name', 'like', $like)
                    ->orWhere('second_name', 'like', $like)
                    ->orWhere('public_profile_tag', 'like', $like);
            });
        });

        return $query;
    }
}
