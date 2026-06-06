<?php

namespace App\Http\Controllers;

use App\Mail\DocumentSigningRequestMail;
use App\Mail\FormFilledNotificationMail;
use App\Models\Claim;
use App\Services\MarkSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    public function viewTemplate($id, Request $request)
    {
        $claim = Claim::with(['partner', 'garage'])->findOrFail($id);

//        Mail::to('_')
//            ->send(new DocumentSigningRequestMail($claim));


//        Mail::to('_')
//            ->send(new FormFilledNotificationMail($claim));
        return view('emails.claims.notification', [
            'claim' => $claim
        ]);
    }
}
