<?php

namespace App\Services\Admin\Settings;

use App\Models\AnimalType;
use App\Models\Animal;

class AnimalTypeService
{
    public function store(array $data): AnimalType
    {
        return AnimalType::create($data);
    }

    public function update(AnimalType $type, array $data): AnimalType
    {
        $type->update($data);
        return $type;
    }

    public function destroy(AnimalType $type): array
    {
        $inUse = Animal::where('animal_type_id', $type->id)->exists();
        if ($inUse) {
            return ['type' => 'error', 'message' => 'Nie można usunąć: typ używany przez zwierzęta.'];
        }
        $type->delete();
        return ['type' => 'success', 'message' => 'Typ usunięty.'];
    }
}
