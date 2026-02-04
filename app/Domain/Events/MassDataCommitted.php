<?php

namespace App\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MassDataCommitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $categoryId,
        public readonly int $feedingsCount,
        public readonly int $weightsCount,
        public readonly int $animalsCount
    ) {
    }
}

