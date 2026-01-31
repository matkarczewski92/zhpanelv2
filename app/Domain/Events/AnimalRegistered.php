<?php

namespace App\Domain\Events;

use App\Models\Animal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnimalRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Animal $animal)
    {
    }
}
