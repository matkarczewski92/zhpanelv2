<?php

namespace App\Application\Api\Services;

class GalleryUrlNormalizer
{
    private const BASE_URL = 'https://makssnake.pl';

    public function normalize(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return self::BASE_URL;
        }

        if (preg_match('#^https?://#i', $trimmed) === 1) {
            return $trimmed;
        }

        if (!str_starts_with($trimmed, '/')) {
            $trimmed = '/' . $trimmed;
        }

        return self::BASE_URL . $trimmed;
    }
}
