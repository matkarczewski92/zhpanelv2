<?php

namespace App\Services\Admin\Settings;

use App\Models\ColorGroup;
use Illuminate\Support\Str;

class ColorGroupService
{
    public function store(array $data): ColorGroup
    {
        return ColorGroup::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function update(ColorGroup $colorGroup, array $data): ColorGroup
    {
        $colorGroup->name = $data['name'];
        $colorGroup->sort_order = (int) ($data['sort_order'] ?? 0);
        $colorGroup->is_active = (bool) ($data['is_active'] ?? true);
        $colorGroup->slug = $this->uniqueSlug($data['name'], $colorGroup->id);
        $colorGroup->save();

        return $colorGroup;
    }

    public function destroy(ColorGroup $colorGroup): array
    {
        if ($colorGroup->animals()->exists()) {
            return ['type' => 'warning', 'message' => 'Grupa jest przypisana do zwierzat i nie moze zostac usunieta.'];
        }

        $colorGroup->delete();

        return ['type' => 'success', 'message' => 'Grupe kolorystyczna usunieto.'];
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'grupa';
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return ColorGroup::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}

