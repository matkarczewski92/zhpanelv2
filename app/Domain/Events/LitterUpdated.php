<?php

namespace App\Domain\Events;

use App\Models\Litter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LitterUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Litter $litter)
    {
    }
}

