<?php

namespace App\Domain\Events;

use App\Models\LitterPregnancyShed;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LitterPregnancyShedRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly LitterPregnancyShed $shed)
    {
    }
}
