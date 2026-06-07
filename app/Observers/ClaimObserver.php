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

    public function updated(Claim $claim): void
    {
        if (
            $claim->wasChanged('status')
            && $claim->status === ClaimStatus::CONFIRMED
        ) {
            ExportClaimToSharePoint::dispatch($claim);
        }
    }
}
