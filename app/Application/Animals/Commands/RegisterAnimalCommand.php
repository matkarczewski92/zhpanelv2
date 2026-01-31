<?php

namespace App\Application\Animals\Commands;

use App\Domain\Events\AnimalRegistered;
use App\Models\Animal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterAnimalCommand
{
    public function handle(array $data): Animal
    {
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
            'public_profile_tag',
            'web_gallery',
        ]);

        // If no tag provided, generate immutable 6-char token.
        if (empty($payload['public_profile_tag'])) {
            $payload['public_profile_tag'] = $this->generateTag();
        }

        if (!array_key_exists('public_profile', $payload)) {
            $payload['public_profile'] = 0;
        }

        $payload = array_filter($payload, static fn ($value) => $value !== null);

        return DB::transaction(function () use ($payload): Animal {
            $animal = Animal::create($payload);

            DB::afterCommit(static function () use ($animal): void {
                event(new AnimalRegistered($animal));
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
