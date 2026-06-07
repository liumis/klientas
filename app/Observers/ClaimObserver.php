<?php

namespace App\Observers;

use App\Enums\ClaimStatus;
use App\Jobs\ExportClaimToSharePoint;
use App\Models\Claim;

class ClaimObserver
{
    public function creating(Claim $claim): void
    {
        if ($claim->status === null) {
            $claim->status = ClaimStatus::REQUEST;
        }
    }

    public function created(Claim $claim): void
    {
        ExportClaimToSharePoint::dispatch($claim);
    }
}
