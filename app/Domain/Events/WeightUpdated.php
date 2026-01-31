<?php

namespace App\Domain\Events;

use App\Models\AnimalWeight;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeightUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AnimalWeight $weight)
    {
    }
}
