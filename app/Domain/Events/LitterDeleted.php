<?php

namespace App\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LitterDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly int $litterId)
    {
    }
}

