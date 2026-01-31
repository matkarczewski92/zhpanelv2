<?php

namespace App\Domain\Events;

use App\Models\AnimalWeight;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeightAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AnimalWeight $weight)
    {
    }
}
