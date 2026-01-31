<?php

namespace App\Domain\Events;

use App\Models\AnimalMolt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoltUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AnimalMolt $molt)
    {
    }
}
