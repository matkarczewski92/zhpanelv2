<?php

namespace App\Application\Animals\Commands;

use App\Models\Animal;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DeleteAnimalCommand
{
    /**
     * Performs two-stage delete logic.
     *
     * @return array{soft:bool, deleted:bool, message:string}
     */
    public function handle(Animal $animal): array
    {
        // stage 1: move to category 5 (Usunięte)
        if ((int) $animal->animal_category_id !== 5) {
            $animal->animal_category_id = 5;
            $animal->save();

            return [
                'soft' => true,
                'deleted' => false,
                'message' => 'Zwierzę przeniesione do: Usunięte.',
            ];
        }

        // stage 2: hard delete
        try {
            DB::transaction(static function () use ($animal): void {
                $animal->delete();
            });

            return [
                'soft' => false,
                'deleted' => true,
                'message' => 'Zwierzę usunięte trwale.',
            ];
        } catch (QueryException $exception) {
            return [
                'soft' => false,
                'deleted' => false,
                'message' => 'Nie można usunąć zwierzęcia: istnieją powiązane rekordy.',
            ];
        }
    }
}
