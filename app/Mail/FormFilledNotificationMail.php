<?php

namespace App\Mail;

use App\Filament\Resources\ClaimResource;
use App\Models\Claim;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class FormFilledNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Claim $claim) {}

    public function build()
    {
        $date = date('Y-m-d H:i');

        $logoUrl = config('services.email.logo_url')
            ?? (is_file(public_path('images/sitandgo-logo.png'))
                ? URL::asset('images/sitandgo-logo.png')
                : 'https://www.sitandgo.lt/wp-content/uploads/2021/12/logo-1-e1640902603550.png');

        return $this->subject("[$date] Užpildyta nauja forma")
            ->view('emails.claims.notification')
            ->with([
                'claim' => $this->claim,
                'logoUrl' => $logoUrl,
                'claimEditUrl' => URL::to(ClaimResource::getUrl('edit', ['record' => $this->claim->id], panel: 'secure')),
            ]);
    }
}
