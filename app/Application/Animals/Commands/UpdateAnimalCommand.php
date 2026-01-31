<?php

namespace App\Application\Animals\Commands;

use App\Domain\Animals\AnimalRepositoryInterface;
use App\Domain\Events\AnimalUpdated;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateAnimalCommand
{
    public function __construct(private readonly AnimalRepositoryInterface $animals)
    {
    }

    public function handle(array $data): Animal
    {
        $animal = $this->animals->findById($data['id']);

        if (!$animal) {
            throw new ModelNotFoundException();
        }

        $payload = Arr::only($data, [
            'name',
            'second_name',
            'sex',
            'date_of_birth',
            'animal_type_id',
            'litter_id',
            'feed_id',
            'feed_interval',
            'animal_category_id',
            'public_profile',
            'web_gallery',
        ]);

        // Preserve existing tag; generate if missing.
        $payload['public_profile_tag'] = $animal->public_profile_tag ?: $this->generateTag();

        return DB::transaction(function () use ($animal, $payload): Animal {
            $animal->fill($payload);
            $animal->save();

            DB::afterCommit(static function () use ($animal): void {
                event(new AnimalUpdated($animal));
            });

            return $animal;
        });
    }

    private function generateTag(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = 6;

        do {
            $tag = collect(range(1, $length))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (Animal::where('public_profile_tag', $tag)->exists());

        return $tag;
    }
}
