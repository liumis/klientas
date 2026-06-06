<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FormFilledNotificationMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Claim $claim) {}

    public function build()
    {
        $date = date("Y-m-d H:i");
        return $this->subject("[$date] Užpildyta nauja forma")
            ->view('emails.claims.notification')
            ->with([
                'claim' => $this->claim
            ]);
    }
}

