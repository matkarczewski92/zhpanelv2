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
        $code = $animal->public_profile_tag ?: (string) $animal->id;

        return [
            'animal_id' => (string) $animal->id,
            'public_profile_tag' => $code,
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

    public function exportCsv(Collection $labels, string $delimiter = ';'): string
    {
        $headers = ['id', 'type', 'name', 'sex', 'date_of_birth', 'code', 'qr_url', 'price'];
        $lines = [];
        $lines[] = implode($delimiter, $headers);
        foreach ($labels as $row) {
            $line = [];
            $line[] = $row['animal_id'] ?? '';
            $line[] = $this->plainText($row['animal_type'] ?? '');
            $line[] = $this->plainText($row['name'] ?? '');
            $line[] = $this->plainText($row['sex'] ?? '');
            $line[] = $row['date_of_birth'] ?? '';
            $line[] = $row['public_profile_tag'] ?? '';
            $line[] = $this->qrUrl($row['public_profile_tag'] ?? '');
            $line[] = $row['price'] ?? '';
            $lines[] = implode($delimiter, array_map(fn ($value) => $this->csvValue($value, $delimiter), $line));
        }
        return implode("\r\n", $lines);
    }

    public function exportCsvWin1250(Collection $labels, string $delimiter = ';'): string
    {
        $utf8 = $this->exportCsv($labels, $delimiter);
        return $this->toWin1250($utf8);
    }

    private function csvValue(?string $value, string $delimiter): string
    {
        $val = (string) $value;
        if (Str::contains($val, [$delimiter, '"', "\n", "\r"])) {
            $val = '"' . str_replace('"', '""', $val) . '"';
        }
        return $val;
    }

    private function sanitizeName(?string $value): string
    {
        $allowed = strip_tags((string) $value, '<b><i><u>');
        return trim(strip_tags($allowed));
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    private function qrUrl(string $code): string
    {
        if ($code === '') {
            return '';
        }

        return 'https://www.makssnake.pl/profile/' . $code;
    }

    private function toWin1250(string $value): string
    {
        $converted = @iconv('UTF-8', 'Windows-1250//TRANSLIT', $value);
        if ($converted === false) {
            $converted = mb_convert_encoding($value, 'Windows-1250', 'UTF-8');
        }
        return $converted;
    }
}
