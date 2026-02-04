<?php

namespace App\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LitterOffspringAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $litterId,
        public readonly int $amount,
    ) {
    }
}

