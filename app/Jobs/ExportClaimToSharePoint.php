<?php

namespace App\Jobs;

use App\Http\Controllers\SharePointController;
use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportClaimToSharePoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Claim $claim) {}

    public function handle(): void
    {
        $success = (new SharePointController)->run($this->claim->id);

        if (! $success) {
            Log::error('SharePoint export failed', ['claim_id' => $this->claim->id]);

            return;
        }

        Log::info('SharePoint export completed', ['claim_id' => $this->claim->id]);
    }
}
