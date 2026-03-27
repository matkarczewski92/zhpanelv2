<?php

namespace App\Application\Animals\Commands;

use App\Application\Animals\Support\QrAnimalResolver;
use App\Application\Animals\Support\QrWorkflowException;
use App\Application\Animals\Support\QrWorkflowResult;
use App\Models\AnimalFeeding;
use Carbon\CarbonImmutable;

class RecordQrFeedingCommand
{
    public function __construct(
        private readonly QrAnimalResolver $resolver,
        private readonly RecordFeedingCommand $recordFeedingCommand,
    ) {
    }

    public function handle(array $data): QrWorkflowResult
    {
        try {
            $animal = $this->resolver->resolve((string) ($data['payload'] ?? ''));
            $animalPayload = $this->resolver->describe($animal);
            $occurredAt = CarbonImmutable::now();

            if (!$animal->feed_id) {
                return QrWorkflowResult::error('To zwierze nie ma ustawionej domyslnej karmy.', 422, 'feeding');
            }

            $duplicateExists = AnimalFeeding::query()
                ->where('animal_id', $animal->id)
                ->whereBetween('created_at', [$occurredAt->startOfDay(), $occurredAt->endOfDay()])
                ->exists();

            if ($duplicateExists && !($data['confirm_duplicate'] ?? false)) {
                return QrWorkflowResult::duplicateConfirmationRequired(
                    'feeding',
                    $animalPayload,
                    'Dla tego weza istnieje juz karmienie z dzisiejsza data. Czy na pewno dodac kolejne?',
                    ['date' => $occurredAt->toDateString()]
                );
            }

            $feeding = $this->recordFeedingCommand->handle([
                'animal_id' => $animal->id,
                'feed_id' => $animal->feed_id,
                'amount' => 1,
                'occurred_at' => $occurredAt->toDateTimeString(),
            ]);

            return QrWorkflowResult::success(
                'feeding',
                $animalPayload,
                'Dodano poprawnie.',
                [
                    'created_id' => (int) $feeding->id,
                    'occurred_at' => optional($feeding->created_at)->toIso8601String(),
                ]
            );
        } catch (QrWorkflowException $exception) {
            return QrWorkflowResult::error($exception->getMessage(), $exception->statusCode(), 'feeding');
        }
    }
}
