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
    public function handle(Animal ): array
    {
        // stage 1: move to category 5 (Usunięte)
        if ((int) ->animal_category_id !== 5) {
            ->animal_category_id = 5;
            ->save();

            return [
                'soft' => true,
                'deleted' => false,
                'message' => 'Zwierzę przeniesione do: Usunięte.',
            ];
        }

        // stage 2: hard delete
        try {
            DB::transaction(static function () use (): void {
                ->delete();
            });

            return [
                'soft' => false,
                'deleted' => true,
                'message' => 'Zwierzę usunięte trwale.',
            ];
        } catch (QueryException ) {
            return [
                'soft' => false,
                'deleted' => false,
                'message' => 'Nie można usunąć zwierzęcia: istnieją powiązane rekordy.',
            ];
        }
    }
}
