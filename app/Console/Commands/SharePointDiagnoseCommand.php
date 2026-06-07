<?php

namespace App\Console\Commands;

use App\Services\SharePointDiagnostics;
use Illuminate\Console\Command;

class SharePointDiagnoseCommand extends Command
{
    protected $signature = 'sharepoint:diagnose';

    protected $description = 'Step-by-step SharePoint/Excel export check with real API error messages';

    public function handle(SharePointDiagnostics $diagnostics): int
    {
        $this->info('SharePoint Excel export diagnostics');
        $this->newLine();

        $failed = false;

        foreach ($diagnostics->run() as $step) {
            $label = $step['ok'] ? 'OK' : 'FAIL';
            $color = $step['ok'] ? 'info' : 'error';

            $this->line("<fg=gray>{$label}</> {$step['step']}");
            $this->{$color}('  '.$step['detail']);
            $this->newLine();

            if (! $step['ok']) {
                $failed = true;
            }
        }

        if ($failed) {
            $this->error('SharePoint export is not ready — fix the first FAIL step above.');

            return self::FAILURE;
        }

        $this->info('All checks passed. Try: php artisan sharepoint:export-claim {id}');

        return self::SUCCESS;
    }
}
