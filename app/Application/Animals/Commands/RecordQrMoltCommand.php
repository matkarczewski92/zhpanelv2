<?php

namespace App\Application\Animals\Commands;

use App\Application\Animals\Support\QrAnimalResolver;
use App\Application\Animals\Support\QrWorkflowException;
use App\Application\Animals\Support\QrWorkflowResult;
use App\Models\AnimalMolt;
use Carbon\CarbonImmutable;

class RecordQrMoltCommand
{
    public function __construct(
        private readonly QrAnimalResolver $resolver,
        private readonly RecordMoltCommand $recordMoltCommand,
    ) {
    }

    public function handle(array $data): QrWorkflowResult
    {
        try {
            $animal = $this->resolver->resolve((string) ($data['payload'] ?? ''));
            $animalPayload = $this->resolver->describe($animal);
            $occurredAt = CarbonImmutable::now();

            $duplicateExists = AnimalMolt::query()
                ->where('animal_id', $animal->id)
                ->whereBetween('created_at', [$occurredAt->startOfDay(), $occurredAt->endOfDay()])
                ->exists();

            if ($duplicateExists && !($data['confirm_duplicate'] ?? false)) {
                return QrWorkflowResult::duplicateConfirmationRequired(
                    'molt',
                    $animalPayload,
                    'Dla tego weza istnieje juz wylinka z dzisiejsza data. Czy na pewno dodac kolejna?',
                    ['date' => $occurredAt->toDateString()]
                );
            }

            $molt = $this->recordMoltCommand->handle([
                'animal_id' => $animal->id,
                'occurred_at' => $occurredAt->toDateTimeString(),
            ]);

            return QrWorkflowResult::success(
                'molt',
                $animalPayload,
                'Dodano poprawnie.',
                [
                    'created_id' => (int) $molt->id,
                    'occurred_at' => optional($molt->created_at)->toIso8601String(),
                ]
            );
        } catch (QrWorkflowException $exception) {
            return QrWorkflowResult::error($exception->getMessage(), $exception->statusCode(), 'molt');
        }
    }
}
