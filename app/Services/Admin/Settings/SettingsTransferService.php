<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalCategory;
use App\Models\AnimalGenotypeCategory;
use App\Models\AnimalGenotypeTrait;
use App\Models\AnimalGenotypeTraitsDictionary;
use App\Models\AnimalType;
use App\Models\ColorGroup;
use App\Models\EwelinkDevice;
use App\Models\Feed;
use App\Models\FinanceCategory;
use App\Models\SystemConfig;
use App\Models\WinteringStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsTransferService
{
    /**
     * @return array<string, mixed>
     */
    public function exportPayload(): array
    {
        return [
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'format_version' => 1,
            ],
            'sections' => [
                'animal_categories' => AnimalCategory::query()
                    ->orderBy('id')
                    ->get(['name'])
                    ->map(fn (AnimalCategory $row): array => ['name' => (string) $row->name])
                    ->all(),
                'animal_types' => AnimalType::query()
                    ->orderBy('id')
                    ->get(['name'])
                    ->map(fn (AnimalType $row): array => ['name' => (string) $row->name])
                    ->all(),
                'genotype_categories' => AnimalGenotypeCategory::query()
                    ->orderBy('id')
                    ->get(['name', 'gene_code', 'gene_type'])
                    ->map(fn (AnimalGenotypeCategory $row): array => [
                        'name' => (string) $row->name,
                        'gene_code' => (string) $row->gene_code,
                        'gene_type' => (string) $row->gene_type,
                    ])
                    ->all(),
                'traits' => AnimalGenotypeTrait::query()
                    ->with(['genes.category:id,gene_code'])
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->map(function (AnimalGenotypeTrait $row): array {
                        $geneCodes = $row->genes
                            ->map(fn (AnimalGenotypeTraitsDictionary $dict): string => strtolower(trim((string) ($dict->category?->gene_code ?? ''))))
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values()
                            ->all();

                        return [
                            'name' => (string) $row->name,
                            'gene_codes' => $geneCodes,
                        ];
                    })
                    ->all(),
                'wintering_stages' => WinteringStage::query()
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get(['order', 'title', 'duration'])
                    ->map(fn (WinteringStage $row): array => [
                        'order' => (int) $row->order,
                        'title' => (string) $row->title,
                        'duration' => (int) $row->duration,
                    ])
                    ->all(),
                'system_config' => SystemConfig::query()
                    ->orderBy('key')
                    ->get(['key', 'name', 'value'])
                    ->map(fn (SystemConfig $row): array => [
                        'key' => (string) $row->key,
                        'name' => (string) $row->name,
                        'value' => (string) $row->value,
                    ])
                    ->all(),
                'feeds' => Feed::query()
                    ->orderBy('id')
                    ->get(['name', 'feeding_interval', 'amount', 'last_price'])
                    ->map(fn (Feed $row): array => [
                        'name' => (string) $row->name,
                        'feeding_interval' => (int) $row->feeding_interval,
                        'amount' => (int) $row->amount,
                        'last_price' => (float) ($row->last_price ?? 0),
                    ])
                    ->all(),
                'finance_categories' => FinanceCategory::query()
                    ->orderBy('id')
                    ->get(['name'])
                    ->map(fn (FinanceCategory $row): array => ['name' => (string) $row->name])
                    ->all(),
                'color_groups' => ColorGroup::query()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['name', 'sort_order', 'is_active'])
                    ->map(fn (ColorGroup $row): array => [
                        'name' => (string) $row->name,
                        'sort_order' => (int) $row->sort_order,
                        'is_active' => (bool) $row->is_active,
                    ])
                    ->all(),
                'ewelink_devices' => EwelinkDevice::query()
                    ->orderBy('id')
                    ->get(['device_id', 'name', 'description', 'device_type'])
                    ->map(fn (EwelinkDevice $row): array => [
                        'device_id' => (string) $row->device_id,
                        'name' => (string) $row->name,
                        'description' => (string) ($row->description ?? ''),
                        'device_type' => (string) ($row->device_type ?? ''),
                    ])
                    ->all(),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    public function buildPreviewFromDecoded(array $decoded): array
    {
        $sectionsInput = $decoded['sections'] ?? $decoded;
        if (!is_array($sectionsInput)) {
            $sectionsInput = [];
        }

        $normalizedSections = $this->normalizeImportedSections($sectionsInput);
        $definitions = $this->sectionDefinitions();
        $previewSections = [];
        $summary = ['new' => 0, 'different' => 0, 'same' => 0];

        foreach ($definitions as $sectionKey => $def) {
            $importedRows = $normalizedSections[$sectionKey] ?? [];
            $existingMap = $this->existingRowsByKey($sectionKey);
            $rows = [];

            foreach ($importedRows as $row) {
                $key = $this->rowKey($sectionKey, $row);
                if ($key === '') {
                    continue;
                }

                $existing = $existingMap[$key] ?? null;
                $status = 'new';
                if (is_array($existing)) {
                    $status = $this->rowsEqual($sectionKey, $row, $existing) ? 'same' : 'different';
                }

                $summary[$status] = (int) ($summary[$status] ?? 0) + 1;
                $rows[] = [
                    'key' => $key,
                    'status' => $status,
                    'data' => $row,
                    'existing' => $existing,
                ];
            }

            $previewSections[$sectionKey] = [
                'label' => (string) $def['label'],
                'fields' => (array) $def['fields'],
                'rows' => $rows,
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'sections' => $previewSections,
        ];
    }

    /**
     * @param array<string, mixed> $inputSections
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function normalizeImportedSections(array $inputSections): array
    {
        $result = [];
        foreach (array_keys($this->sectionDefinitions()) as $sectionKey) {
            $rows = $inputSections[$sectionKey] ?? [];
            if (!is_array($rows)) {
                $rows = [];
            }

            $normalizedRows = collect($rows)
                ->filter(fn (mixed $row): bool => is_array($row))
                ->map(fn (array $row): array => $this->normalizeRow($sectionKey, $row))
                ->filter(fn (array $row): bool => $this->rowKey($sectionKey, $row) !== '')
                ->values()
                ->all();

            $unique = [];
            foreach ($normalizedRows as $row) {
                $unique[$this->rowKey($sectionKey, $row)] = $row;
            }

            $result[$sectionKey] = array_values($unique);
        }

        return $result;
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $sections
     * @return array<string, int>
     */
    public function applyImport(array $sections, bool $replaceAll): array
    {
        $normalizedSections = $this->normalizeImportedSections($sections);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($normalizedSections, $replaceAll, &$stats): void {
            foreach ($normalizedSections as $sectionKey => $rows) {
                foreach ($rows as $row) {
                    $existing = $this->findExistingModel($sectionKey, $row);
                    if ($existing === null) {
                        $this->createModel($sectionKey, $row);
                        $stats['created']++;
                        continue;
                    }

                    $existingRow = $this->modelToRow($sectionKey, $existing);
                    $isDifferent = !$this->rowsEqual($sectionKey, $row, $existingRow);
                    if (!$replaceAll && !$isDifferent) {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($replaceAll || $isDifferent) {
                        $this->updateModel($sectionKey, $existing, $row);
                        $stats['updated']++;
                    }
                }
            }
        });

        return $stats;
    }

    /**
     * @return array<string, array{label:string,fields:array<int, array{key:string,label:string,type:string}>}>
     */
    public function sectionDefinitions(): array
    {
        return [
            'animal_categories' => [
                'label' => 'Kategorie',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                ],
            ],
            'animal_types' => [
                'label' => 'Typy',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                ],
            ],
            'genotype_categories' => [
                'label' => 'Genotyp: Kategorie',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'gene_code', 'label' => 'Kod', 'type' => 'text'],
                    ['key' => 'gene_type', 'label' => 'Typ', 'type' => 'text'],
                ],
            ],
            'traits' => [
                'label' => 'Genotyp: Traits',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'gene_codes', 'label' => 'Gene codes (CSV)', 'type' => 'csv'],
                ],
            ],
            'wintering_stages' => [
                'label' => 'Zimowanie: Etapy',
                'fields' => [
                    ['key' => 'order', 'label' => 'Kolejność', 'type' => 'number'],
                    ['key' => 'title', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'duration', 'label' => 'Czas (dni)', 'type' => 'number'],
                ],
            ],
            'system_config' => [
                'label' => 'System config',
                'fields' => [
                    ['key' => 'key', 'label' => 'Klucz', 'type' => 'text'],
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'value', 'label' => 'Wartość', 'type' => 'textarea'],
                ],
            ],
            'feeds' => [
                'label' => 'Karma',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'feeding_interval', 'label' => 'Interwał', 'type' => 'number'],
                    ['key' => 'amount', 'label' => 'Ilość', 'type' => 'number'],
                    ['key' => 'last_price', 'label' => 'Cena', 'type' => 'number'],
                ],
            ],
            'finance_categories' => [
                'label' => 'Kategorie finansowe',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                ],
            ],
            'color_groups' => [
                'label' => 'Grupy kolorystyczne',
                'fields' => [
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'sort_order', 'label' => 'Sort', 'type' => 'number'],
                    ['key' => 'is_active', 'label' => 'Aktywna', 'type' => 'bool'],
                ],
            ],
            'ewelink_devices' => [
                'label' => 'eWeLink: Urządzenia',
                'fields' => [
                    ['key' => 'device_id', 'label' => 'Device ID', 'type' => 'text'],
                    ['key' => 'name', 'label' => 'Nazwa', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Opis', 'type' => 'text'],
                    ['key' => 'device_type', 'label' => 'Typ', 'type' => 'text'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(string $sectionKey, array $row): array
    {
        return match ($sectionKey) {
            'animal_categories', 'animal_types', 'finance_categories' => [
                'name' => $this->normalizeText($row['name'] ?? ''),
            ],
            'genotype_categories' => [
                'name' => $this->normalizeText($row['name'] ?? ''),
                'gene_code' => strtolower($this->normalizeText($row['gene_code'] ?? '')),
                'gene_type' => strtolower(substr($this->normalizeText($row['gene_type'] ?? ''), 0, 2)),
            ],
            'traits' => [
                'name' => $this->normalizeText($row['name'] ?? ''),
                'gene_codes' => $this->normalizeGeneCodes($row['gene_codes'] ?? []),
            ],
            'wintering_stages' => [
                'order' => max(1, (int) ($row['order'] ?? 1)),
                'title' => $this->normalizeText($row['title'] ?? ''),
                'duration' => max(0, (int) ($row['duration'] ?? 0)),
            ],
            'system_config' => [
                'key' => $this->normalizeText($row['key'] ?? ''),
                'name' => $this->normalizeText($row['name'] ?? ''),
                'value' => trim((string) ($row['value'] ?? '')),
            ],
            'feeds' => [
                'name' => $this->normalizeText($row['name'] ?? ''),
                'feeding_interval' => max(0, (int) ($row['feeding_interval'] ?? 0)),
                'amount' => max(0, (int) ($row['amount'] ?? 0)),
                'last_price' => is_numeric($row['last_price'] ?? null) ? (float) $row['last_price'] : 0.0,
            ],
            'color_groups' => [
                'name' => $this->normalizeText($row['name'] ?? ''),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => (bool) ($row['is_active'] ?? true),
            ],
            'ewelink_devices' => [
                'device_id' => $this->normalizeText($row['device_id'] ?? ''),
                'name' => $this->normalizeText($row['name'] ?? ''),
                'description' => trim((string) ($row['description'] ?? '')),
                'device_type' => $this->normalizeText($row['device_type'] ?? ''),
            ],
            default => [],
        };
    }

    /**
     * @param array<int, string>|string $value
     * @return array<int, string>
     */
    private function normalizeGeneCodes(array|string $value): array
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return collect($values)
            ->map(fn (mixed $code): string => strtolower(trim((string) $code)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowKey(string $sectionKey, array $row): string
    {
        return match ($sectionKey) {
            'animal_categories', 'animal_types', 'finance_categories', 'traits' => strtolower((string) ($row['name'] ?? '')),
            'genotype_categories' => strtolower((string) ($row['gene_code'] ?? '')),
            'wintering_stages' => (string) ((int) ($row['order'] ?? 0)),
            'system_config' => strtolower((string) ($row['key'] ?? '')),
            'feeds' => strtolower((string) ($row['name'] ?? '')),
            'color_groups' => strtolower((string) ($row['name'] ?? '')),
            'ewelink_devices' => strtolower((string) ($row['device_id'] ?? '')),
            default => '',
        };
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function existingRowsByKey(string $sectionKey): array
    {
        $map = [];
        $models = match ($sectionKey) {
            'animal_categories' => AnimalCategory::query()->get(),
            'animal_types' => AnimalType::query()->get(),
            'genotype_categories' => AnimalGenotypeCategory::query()->get(),
            'traits' => AnimalGenotypeTrait::query()->with('genes.category')->get(),
            'wintering_stages' => WinteringStage::query()->get(),
            'system_config' => SystemConfig::query()->get(),
            'feeds' => Feed::query()->get(),
            'finance_categories' => FinanceCategory::query()->get(),
            'color_groups' => ColorGroup::query()->get(),
            'ewelink_devices' => EwelinkDevice::query()->get(),
            default => collect(),
        };

        foreach ($models as $model) {
            $normalized = $this->modelToRow($sectionKey, $model);
            $key = $this->rowKey($sectionKey, $normalized);
            if ($key !== '') {
                $map[$key] = $normalized;
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function rowsEqual(string $sectionKey, array $left, array $right): bool
    {
        $leftN = $this->normalizeRow($sectionKey, $left);
        $rightN = $this->normalizeRow($sectionKey, $right);

        return $leftN == $rightN;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function findExistingModel(string $sectionKey, array $row): mixed
    {
        return match ($sectionKey) {
            'animal_categories' => AnimalCategory::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'animal_types' => AnimalType::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'genotype_categories' => AnimalGenotypeCategory::query()->whereRaw('LOWER(gene_code)=?', [mb_strtolower((string) $row['gene_code'])])->first(),
            'traits' => AnimalGenotypeTrait::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'wintering_stages' => WinteringStage::query()->where('order', (int) ($row['order'] ?? 0))->first(),
            'system_config' => SystemConfig::query()->whereRaw('LOWER(`key`)=?', [mb_strtolower((string) $row['key'])])->first(),
            'feeds' => Feed::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'finance_categories' => FinanceCategory::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'color_groups' => ColorGroup::query()->whereRaw('LOWER(name)=?', [mb_strtolower((string) $row['name'])])->first(),
            'ewelink_devices' => EwelinkDevice::query()->whereRaw('LOWER(device_id)=?', [mb_strtolower((string) $row['device_id'])])->first(),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function modelToRow(string $sectionKey, mixed $model): array
    {
        if ($sectionKey === 'traits' && $model instanceof AnimalGenotypeTrait) {
            $geneCodes = $model->relationLoaded('genes')
                ? $model->genes
                    ->map(fn (AnimalGenotypeTraitsDictionary $dict): string => strtolower(trim((string) ($dict->category?->gene_code ?? ''))))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all()
                : [];

            return [
                'name' => $this->normalizeText($model->name),
                'gene_codes' => $geneCodes,
            ];
        }

        return $this->normalizeRow($sectionKey, $model->toArray());
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createModel(string $sectionKey, array $row): void
    {
        match ($sectionKey) {
            'animal_categories' => AnimalCategory::query()->create(['name' => $row['name']]),
            'animal_types' => AnimalType::query()->create(['name' => $row['name']]),
            'genotype_categories' => AnimalGenotypeCategory::query()->create([
                'name' => $row['name'],
                'gene_code' => $row['gene_code'],
                'gene_type' => $row['gene_type'],
            ]),
            'traits' => $this->createTrait($row),
            'wintering_stages' => WinteringStage::query()->create([
                'order' => $row['order'],
                'title' => $row['title'],
                'duration' => $row['duration'],
            ]),
            'system_config' => SystemConfig::query()->create([
                'key' => $row['key'],
                'name' => $row['name'],
                'value' => $row['value'],
            ]),
            'feeds' => Feed::query()->create([
                'name' => $row['name'],
                'feeding_interval' => $row['feeding_interval'],
                'amount' => $row['amount'],
                'last_price' => $row['last_price'],
            ]),
            'finance_categories' => FinanceCategory::query()->create(['name' => $row['name']]),
            'color_groups' => ColorGroup::query()->create([
                'name' => $row['name'],
                'slug' => $this->generateUniqueColorGroupSlug($row['name']),
                'sort_order' => $row['sort_order'],
                'is_active' => $row['is_active'],
            ]),
            'ewelink_devices' => EwelinkDevice::query()->create([
                'device_id' => $row['device_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'device_type' => $row['device_type'],
            ]),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function updateModel(string $sectionKey, mixed $model, array $row): void
    {
        match ($sectionKey) {
            'animal_categories', 'animal_types', 'finance_categories' => $model->update(['name' => $row['name']]),
            'genotype_categories' => $model->update([
                'name' => $row['name'],
                'gene_code' => $row['gene_code'],
                'gene_type' => $row['gene_type'],
            ]),
            'traits' => $this->updateTrait($model, $row),
            'wintering_stages' => $model->update([
                'order' => $row['order'],
                'title' => $row['title'],
                'duration' => $row['duration'],
            ]),
            'system_config' => $model->update([
                'name' => $row['name'],
                'value' => $row['value'],
            ]),
            'feeds' => $model->update([
                'name' => $row['name'],
                'feeding_interval' => $row['feeding_interval'],
                'amount' => $row['amount'],
                'last_price' => $row['last_price'],
            ]),
            'color_groups' => $model->update([
                'name' => $row['name'],
                'slug' => $this->generateUniqueColorGroupSlug($row['name'], (int) $model->id),
                'sort_order' => $row['sort_order'],
                'is_active' => $row['is_active'],
            ]),
            'ewelink_devices' => $model->update([
                'name' => $row['name'],
                'description' => $row['description'],
                'device_type' => $row['device_type'],
            ]),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createTrait(array $row): void
    {
        $trait = AnimalGenotypeTrait::query()->create([
            'name' => $row['name'],
        ]);

        $this->syncTraitGenes($trait, (array) ($row['gene_codes'] ?? []));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function updateTrait(AnimalGenotypeTrait $trait, array $row): void
    {
        $trait->update([
            'name' => $row['name'],
        ]);

        $this->syncTraitGenes($trait, (array) ($row['gene_codes'] ?? []));
    }

    /**
     * @param array<int, string> $geneCodes
     */
    private function syncTraitGenes(AnimalGenotypeTrait $trait, array $geneCodes): void
    {
        $geneCodesNormalized = $this->normalizeGeneCodes($geneCodes);
        $categoryIds = AnimalGenotypeCategory::query()
            ->get(['id', 'gene_code'])
            ->filter(fn (AnimalGenotypeCategory $category): bool => in_array(strtolower((string) $category->gene_code), $geneCodesNormalized, true))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        AnimalGenotypeTraitsDictionary::query()
            ->where('trait_id', $trait->id)
            ->whereNotIn('category_id', $categoryIds)
            ->delete();

        foreach ($categoryIds as $categoryId) {
            AnimalGenotypeTraitsDictionary::query()->firstOrCreate([
                'trait_id' => $trait->id,
                'category_id' => $categoryId,
            ]);
        }
    }

    private function generateUniqueColorGroupSlug(string $name, int $ignoreId = 0): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'grupa';
        $slug = $base;
        $i = 2;

        while (
            ColorGroup::query()
                ->where('slug', $slug)
                ->when($ignoreId > 0, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function normalizeText(mixed $value): string
    {
        return trim((string) $value);
    }
}
