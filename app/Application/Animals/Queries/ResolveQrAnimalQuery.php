<?php

namespace App\Application\Animals\Queries;

use App\Application\Animals\Support\QrAnimalResolver;
use App\Application\Animals\Support\QrWorkflowException;
use App\Application\Animals\Support\QrWorkflowResult;

class ResolveQrAnimalQuery
{
    public function __construct(
        private readonly QrAnimalResolver $resolver
    ) {
    }

    public function handle(array $data): QrWorkflowResult
    {
        try {
            $animal = $this->resolver->resolve((string) ($data['payload'] ?? ''));

            return QrWorkflowResult::resolved($this->resolver->describe($animal));
        } catch (QrWorkflowException $exception) {
            return QrWorkflowResult::error($exception->getMessage(), $exception->statusCode());
        }
    }
}
