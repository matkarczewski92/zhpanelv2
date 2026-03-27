<?php

namespace App\Application\Animals\Commands;

use App\Application\Animals\Support\QrAnimalResolver;
use App\Application\Animals\Support\QrWorkflowException;
use App\Application\Animals\Support\QrWorkflowResult;
use App\Models\AnimalWeight;
use Carbon\CarbonImmutable;

class RecordQrWeightCommand
{
    public function __construct(
        private readonly QrAnimalResolver $resolver,
        private readonly AddWeightCommand $addWeightCommand,
    ) {
    }

    public function handle(array $data): QrWorkflowResult
    {
        try {
            $animal = $this->resolver->resolve((string) ($data['payload'] ?? ''));
            $animalPayload = $this->resolver->describe($animal);
            $occurredAt = CarbonImmutable::now();
            $value = (float) ($data['value'] ?? 0);

            $duplicateExists = AnimalWeight::query()
                ->where('animal_id', $animal->id)
                ->whereBetween('created_at', [$occurredAt->startOfDay(), $occurredAt->endOfDay()])
                ->exists();

            if ($duplicateExists && !($data['confirm_duplicate'] ?? false)) {
                return QrWorkflowResult::duplicateConfirmationRequired(
                    'weight',
                    $animalPayload,
                    'Dla tego weza istnieje juz wpis wagi z dzisiejsza data. Czy na pewno dodac kolejny?',
                    [
                        'date' => $occurredAt->toDateString(),
                        'value' => $value,
                    ]
                );
            }

            $weight = $this->addWeightCommand->handle([
                'animal_id' => $animal->id,
                'value' => $value,
                'occurred_at' => $occurredAt->toDateTimeString(),
            ]);

            return QrWorkflowResult::success(
                'weight',
                $animalPayload,
                'Dodano poprawnie.',
                [
                    'created_id' => (int) $weight->id,
                    'occurred_at' => optional($weight->created_at)->toIso8601String(),
                    'value' => (float) $weight->value,
                ]
            );
        } catch (QrWorkflowException $exception) {
            return QrWorkflowResult::error($exception->getMessage(), $exception->statusCode(), 'weight');
        }
    }
}
