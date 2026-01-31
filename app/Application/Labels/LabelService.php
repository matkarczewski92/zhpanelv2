<?php

namespace App\Application\Labels;

use App\Domain\Shared\Enums\Sex;
use App\Models\Animal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LabelService
{
    /**
     * Build label data for a single animal.
     *
     * @return array<string, string|null>
     */
    public function buildLabel(Animal $animal): array
    {
        $animal->loadMissing(['animalType', 'litter', 'offers' => function ($q) {
            $q->latest('created_at')->limit(1);
        }]);

        $name = $this->sanitizeName($animal->name);
        $second = $animal->second_name ? '"' . e($animal->second_name) . '" ' : '';
        $offer = $animal->offers->first();

        return [
            'animal_id' => (string) $animal->id,
            'public_profile_tag' => $animal->public_profile_tag ?? '',
            'animal_type' => $animal->animalType?->name ?? '',
            'litter_code' => $animal->litter?->litter_code ?? '',
            'name' => $second . $name,
            'sex' => Sex::label((int) $animal->sex),
            'date_of_birth' => optional($animal->date_of_birth)->format('Y-m-d'),
            'price' => $offer?->price ? number_format($offer->price, 2, '.', '') : '',
        ];
    }

    /**
     * @param iterable<int> $animalIds
     * @return Collection<int, array<string,string|null>>
     */
    public function buildMany(iterable $animalIds): Collection
    {
        $animals = Animal::query()
            ->with(['animalType', 'litter', 'offers' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->whereIn('id', $animalIds)
            ->orderBy('id')
            ->get();

        return $animals->map(fn ($animal) => $this->buildLabel($animal));
    }

    public function exportCsv(Collection $labels): string
    {
        $headers = ['id', 'type', 'name', 'sex', 'date_of_birth', 'code', 'qr_url', 'price'];
        $lines = [];
        $lines[] = implode(';', $headers);
        foreach ($labels as $row) {
            $line = [];
            $line[] = $row['animal_id'] ?? '';
            $line[] = $row['animal_type'] ?? '';
            $line[] = $row['name'] ?? '';
            $line[] = $row['sex'] ?? '';
            $line[] = $row['date_of_birth'] ?? '';
            $line[] = $row['public_profile_tag'] ?? '';
            $line[] = $this->qrUrl($row['public_profile_tag'] ?? '');
            $line[] = $row['price'] ?? '';
            $lines[] = implode(';', array_map([$this, 'csvValue'], $line));
        }
        return implode("\n", $lines);
    }

    private function csvValue(?string $value): string
    {
        $val = (string) $value;
        if (Str::contains($val, [';', '"', "\n"])) {
            $val = '"' . str_replace('"', '""', $val) . '"';
        }
        return $val;
    }

    private function sanitizeName(?string $value): string
    {
        $allowed = strip_tags((string) $value, '<b><i><u>');
        return trim(strip_tags($allowed));
    }

    private function qrUrl(string $code): string
    {
        if ($code === '') {
            return '';
        }

        return 'https://www.makssnake.pl/profile/' . $code;
    }
}
