<?php

namespace App\Application\Animals\Services;

use App\Models\Animal;

class SecretTagGenerator
{
    public function generate(int $length = 6): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $size = max(5, min(7, $length));

        do {
            $tag = '';
            for ($i = 0; $i < $size; $i++) {
                $tag .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Animal::query()->where('secret_tag', $tag)->exists());

        return $tag;
    }
}
