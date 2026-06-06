<?php

namespace App\Jobs;

use App\Enums\ClaimStatus;
use App\Mail\DocumentSigningRequestMail;
use App\Models\Claim;
use App\Services\MarkSignService;
use App\Services\MicrosoftGraphMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateClaimPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Claim $claim) {}

    public function handle(MarkSignService $markSign): void
    {
        $this->claim->refresh();
        $this->claim->load(['partner', 'garage']);

        Log::info('GenerateClaimPdf started', ['claim_id' => $this->claim->id]);

        try {
            $pdf = Pdf::loadView('pdf.claim', ['claim' => $this->claim]);
            $tempPath = 'temp/claim_'.$this->claim->id.'.pdf';
            Storage::disk('local')->put($tempPath, $pdf->output());

            $uuid = $markSign->uploadDocument($tempPath);
            $signingUrl = $markSign->generateSigningLink($uuid, $this->claim);

            $this->claim->update([
                'marksign_uuid' => $uuid,
                'signing_url' => $signingUrl,
                'status' => ClaimStatus::AwaitingSignature,
            ]);

            Log::info('GenerateClaimPdf MarkSign OK', [
                'claim_id' => $this->claim->id,
                'marksign_uuid' => $uuid,
            ]);

            try {
                $this->claim->refresh();

                app(MicrosoftGraphMailService::class)->send(
                    new DocumentSigningRequestMail($this->claim),
                    $this->claim->email,
                );

                Log::info('GenerateClaimPdf signing email sent', [
                    'claim_id' => $this->claim->id,
                    'email' => $this->claim->email,
                ]);
            } catch (Throwable $mailException) {
                Log::error('GenerateClaimPdf mail failed (claim still has signing link)', [
                    'claim_id' => $this->claim->id,
                    'email' => $this->claim->email,
                    'message' => $mailException->getMessage(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('GenerateClaimPdf failed', [
                'claim_id' => $this->claim->id,
                'message' => $e->getMessage(),
            ]);

            $this->claim->update(['status' => ClaimStatus::Error]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $claim = Claim::find($this->claim->id);

        if ($claim && $claim->status === ClaimStatus::Pending) {
            $claim->update(['status' => ClaimStatus::Error]);
        }

        Log::error('GenerateClaimPdf job failed permanently', [
            'claim_id' => $this->claim->id,
            'message' => $exception?->getMessage(),
        ]);
    }
}
