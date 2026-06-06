<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentSigningRequestMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Claim $claim) {}

    public function build()
    {
        return $this->subject('Reikalingas dokumentų pasirašymas')
            ->view('emails.claims.signing-request')
            ->with([
                'claim' => $this->claim
            ]);
    }
}

