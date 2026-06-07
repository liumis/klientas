<?php

namespace App\Console\Commands;

use App\Models\Claim;
use App\Models\EmailSetting;
use App\Models\Setting;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseIntegrationsCommand extends Command
{
    protected $signature = 'integrations:diagnose {claim_id?}';

    protected $description = 'Check queue, email settings, SharePoint settings, and latest claim';

    public function handle(): int
    {
        $this->info('=== Queue ===');
        if (Schema::hasTable('jobs')) {
            $this->line('Pending jobs: '.DB::table('jobs')->count());
        }
        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
            $this->line('Failed jobs: '.$failed);
            if ($failed > 0) {
                $last = DB::table('failed_jobs')->orderByDesc('id')->first();
                $this->warn('Latest failure (excerpt):');
                $this->line(substr((string) $last->exception, 0, 800));
            }
        }

        $this->newLine();
        $this->info('=== Email (O365 / Graph) ===');
        $email = EmailSetting::current();
        if ($email === null) {
            $this->warn('No row in email_settings — configure /secure/email-settings');
        } else {
            $mail = app(MicrosoftGraphMailService::class);
            $this->line('Email settings row: yes');
            $this->line('Graph mail configured: '.($mail->isConfigured() ? 'yes' : 'no (check tenant/client/secret/mail)'));
            $this->line('Mailbox (mail): '.($email->mail ?? '(empty)'));
        }

        $this->newLine();
        $this->info('=== SharePoint (Excel) ===');
        $sharePointSettings = Setting::query()
            ->where('scope', 'sharepoint')
            ->pluck('value', 'setting');
        if ($sharePointSettings->isEmpty()) {
            $this->warn('No SharePoint settings row — Excel export will fail until configured.');
        } else {
            $this->line('SharePoint settings row: yes');
            $this->line('Site: '.($sharePointSettings->get('site_name') ?? '(empty)'));
            $this->line('File: '.($sharePointSettings->get('file_name') ?? '(empty)'));
        }

        $claimId = $this->argument('claim_id');
        $claim = $claimId
            ? Claim::find($claimId)
            : Claim::query()->latest()->first();

        if ($claim) {
            $this->newLine();
            $this->info("=== Claim #{$claim->id} ===");
            $this->line('Status: '.($claim->status?->value ?? '(null)'));
            $this->line('Email: '.$claim->email);
            $this->line('Claim number: '.$claim->claim_number);
        }

        return self::SUCCESS;
    }
}
