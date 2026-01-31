<?php

namespace App\Domain\Events;

use App\Models\Animal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnimalUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Animal $animal)
    {
    }
}
