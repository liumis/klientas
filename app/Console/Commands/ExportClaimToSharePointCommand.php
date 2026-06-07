<?php

namespace App\Console\Commands;

use App\Http\Controllers\SharePointController;
use App\Models\Claim;
use App\Services\SharePointDiagnostics;
use Illuminate\Console\Command;

class ExportClaimToSharePointCommand extends Command
{
    protected $signature = 'sharepoint:export-claim {claim_id : Claim ID to export}';

    protected $description = 'Append a claim row to the SharePoint Excel sheet (same as patvirtinta status export)';

    public function handle(): int
    {
        $claim = Claim::find($this->argument('claim_id'));

        if ($claim === null) {
            $this->error('Claim not found.');

            return self::FAILURE;
        }

        $this->info("Exporting claim #{$claim->id} ({$claim->first_name} {$claim->last_name})...");

        $controller = new SharePointController;
        $row = $controller->buildClaimRow($claim);
        $this->line('Row: '.json_encode($row, JSON_UNESCAPED_UNICODE));

        if ($controller->run($claim->id)) {
            $this->info('SharePoint export succeeded.');

            return self::SUCCESS;
        }

        $this->error('SharePoint export failed.');
        $this->line('Run: php artisan sharepoint:diagnose');

        return self::FAILURE;
    }
}
