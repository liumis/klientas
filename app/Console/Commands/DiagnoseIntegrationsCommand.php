<?php

namespace App\Console\Commands;

use App\Mail\DocumentSigningRequestMail;
use App\Models\Claim;
use App\Models\EmailSetting;
use App\Services\MarkSignService;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DiagnoseIntegrationsCommand extends Command
{
    protected $signature = 'integrations:diagnose {claim_id?} {--test-upload : Try a MarkSign PDF upload using an existing temp PDF} {--test-mail : Send signing email for the claim}';

    protected $description = 'Check queue, MarkSign token, email settings, and latest claim integration fields';

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
        $this->info('=== MarkSign ===');
        $token = config('services.marksign.token');
        $this->line('MARKSIGN_TOKEN configured: '.(filled($token) ? 'yes ('.strlen((string) $token).' chars)' : 'NO'));
        $this->line('APP_URL: '.config('app.url'));
        $this->line('Callback route: '.route('marksign.callback'));

        $markSign = app(MarkSignService::class);
        $this->line('MarkSignService configured: '.($markSign->isConfigured() ? 'yes' : 'no'));

        if ($this->option('test-upload') && $markSign->isConfigured()) {
            $testPath = 'temp/diagnose_marksign_test.pdf';
            if (! Storage::disk('local')->exists($testPath)) {
                $claimId = $this->argument('claim_id') ?? Claim::query()->latest()->value('id');
                $candidate = $claimId ? "temp/claim_{$claimId}.pdf" : null;
                $testPath = ($candidate && Storage::disk('local')->exists($candidate))
                    ? $candidate
                    : $testPath;
            }

            if ($testPath === 'temp/diagnose_marksign_test.pdf' && ! Storage::disk('local')->exists($testPath)) {
                $this->warn('No temp PDF found — submit/Pergeneruoti a claim first, then rerun with --test-upload');
            } else {
                try {
                    $uuid = $markSign->uploadDocument($testPath);
                    $this->info("MarkSign test upload OK: {$uuid}");
                } catch (\Throwable $e) {
                    $this->error('MarkSign test upload failed: '.$e->getMessage());
                }
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

        $claimId = $this->argument('claim_id');
        $claim = $claimId
            ? Claim::with(['partner', 'garage'])->find($claimId)
            : Claim::query()->latest()->first();

        if ($claim) {
            $this->newLine();
            $this->info("=== Claim #{$claim->id} ===");
            $this->line('Status: '.($claim->status?->value ?? '(null)'));
            $this->line('Email: '.$claim->email);
            $this->line('marksign_uuid: '.($claim->marksign_uuid ?? '(empty)'));
            $this->line('signing_url: '.($claim->signing_url ? 'set' : '(empty)'));

            if ($claim->marksign_uuid && $markSign->isConfigured()) {
                try {
                    $status = $markSign->getDocumentStatus($claim->marksign_uuid);
                    $signer = $status['signers'][0] ?? null;
                    $this->line('MarkSign signer status: '.($signer['signStatus'] ?? json_encode($status)));
                } catch (\Throwable $e) {
                    $this->error('MarkSign status API: '.$e->getMessage());
                }
            }

            if ($this->option('test-mail')) {
                if (! filled($claim->signing_url)) {
                    $this->warn('Claim has no signing_url — run Pergeneruoti first.');
                } else {
                    try {
                        app(MicrosoftGraphMailService::class)->send(
                            new DocumentSigningRequestMail($claim),
                            $claim->email,
                        );
                        $this->info("Signing email sent to: {$claim->email}");
                    } catch (\Throwable $e) {
                        $this->error('Test mail failed: '.$e->getMessage());
                    }
                }
            }
        }

        $this->newLine();
        $this->comment('Documents appear in the MarkSign account that owns MARKSIGN_TOKEN — not necessarily every login.');

        return self::SUCCESS;
    }
}
