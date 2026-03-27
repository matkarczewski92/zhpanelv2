<?php

namespace App\Application\Animals\Support;

use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;

class QrAnimalResolver
{
    public function resolve(string $payload): Animal
    {
        $publicTag = $this->extractPublicTag($payload);

        $animals = Animal::query()
            ->with('feed:id,name')
            ->whereNotNull('public_profile_tag')
            ->whereRaw('LOWER(public_profile_tag) = ?', [mb_strtolower($publicTag)])
            ->get([
                'id',
                'name',
                'second_name',
                'sex',
                'feed_id',
                'public_profile_tag',
            ]);

        if ($animals->isEmpty()) {
            throw new QrWorkflowException('Nie znaleziono zwierzecia dla zeskanowanego kodu.', 404);
        }

        if ($animals->count() > 1) {
            throw new QrWorkflowException('Ten publiczny tag jest przypisany do kilku zwierzat. Popraw dane przed skanowaniem.', 409);
        }

        return $animals->first();
    }

    public function describe(Animal $animal): array
    {
        $main = $this->sanitizeName($animal->name);
        $second = $this->sanitizeName($animal->second_name);
        $label = trim($second !== '' ? $second . ' ' . $main : $main);

        return [
            'id' => (int) $animal->id,
            'name' => $main !== '' ? $main : '-',
            'second_name' => $second,
            'label' => $label !== '' ? $label : ('#' . $animal->id),
            'public_tag' => (string) ($animal->public_profile_tag ?? ''),
            'sex_label' => Sex::label((int) $animal->sex),
            'default_feed_name' => $animal->feed?->name,
        ];
    }

    public function extractPublicTag(string $payload): string
    {
        $value = trim($payload);

        if ($value === '') {
            throw new QrWorkflowException('Zeskanowany kod jest pusty.', 422);
        }

        if ($this->isSafeTag($value)) {
            return $value;
        }

        $parts = parse_url($value);

        if ($parts === false || !isset($parts['scheme'], $parts['host'], $parts['path'])) {
            throw new QrWorkflowException('Kod QR ma nieobslugiwany format.', 422);
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new QrWorkflowException('Kod QR ma nieobslugiwany format.', 422);
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            throw new QrWorkflowException('Kod QR ma nieobslugiwany format.', 422);
        }

        $host = strtolower((string) $parts['host']);

        if (!in_array($host, $this->allowedHosts(), true)) {
            throw new QrWorkflowException('Kod QR pochodzi z nieobslugiwanej domeny.', 422);
        }

        $segments = array_values(array_filter(explode('/', trim((string) $parts['path'], '/'))));

        if (count($segments) !== 2 || strtolower($segments[0]) !== 'profile' || !$this->isSafeTag($segments[1])) {
            throw new QrWorkflowException('Kod QR ma nieobslugiwany format profilu.', 422);
        }

        return $segments[1];
    }

    /**
     * @return array<int, string>
     */
    private function allowedHosts(): array
    {
        $hosts = ['makssnake.pl', 'www.makssnake.pl'];
        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appUrlHost) && $appUrlHost !== '') {
            $hosts[] = strtolower($appUrlHost);
        }

        return array_values(array_unique($hosts));
    }

    private function isSafeTag(string $value): bool
    {
        return preg_match('/\A[a-zA-Z0-9_-]{1,120}\z/', $value) === 1;
    }

    private function sanitizeName(?string $value): string
    {
        return trim(strip_tags((string) $value, '<b><i><u>'));
    }
}
