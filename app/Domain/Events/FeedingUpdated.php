<?php

namespace App\Domain\Events;

use App\Models\AnimalFeeding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedingUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AnimalFeeding $feeding)
    {
    }
}
