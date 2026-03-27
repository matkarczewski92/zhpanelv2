<?php

namespace App\Application\Animals\Support;

class QrWorkflowResult
{
    private function __construct(
        private readonly string $status,
        private readonly string $message,
        private readonly int $statusCode,
        private readonly ?string $mode = null,
        private readonly ?array $animal = null,
        private readonly array $data = [],
    ) {
    }

    public static function resolved(array $animal, string $message = 'Zwierze znalezione.'): self
    {
        return new self('resolved', $message, 200, null, $animal);
    }

    public static function success(
        string $mode,
        array $animal,
        string $message = 'Dodano poprawnie.',
        array $data = [],
    ): self {
        return new self('success', $message, 200, $mode, $animal, $data);
    }

    public static function duplicateConfirmationRequired(
        string $mode,
        array $animal,
        string $message,
        array $data = [],
    ): self {
        return new self('duplicate_confirmation_required', $message, 409, $mode, $animal, $data);
    }

    public static function error(string $message, int $statusCode = 422, ?string $mode = null): self
    {
        return new self('error', $message, $statusCode, $mode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        $payload = [
            'status' => $this->status,
            'message' => $this->message,
            'mode' => $this->mode,
            'animal' => $this->animal,
            'data' => $this->data,
        ];

        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== null && $value !== []
        );
    }
}
