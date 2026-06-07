<?php

namespace App\Http\Controllers;

use App\Filament\Resources\ClaimResource;
use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MailController extends Controller
{
    public function viewTemplate($id, Request $request)
    {
        $claim = Claim::findOrFail($id);

        $logoUrl = config('services.email.logo_url')
            ?? (is_file(public_path('images/sitandgo-logo.png'))
                ? URL::asset('images/sitandgo-logo.png')
                : 'https://www.sitandgo.lt/wp-content/uploads/2021/12/logo-1-e1640902603550.png');

        return view('emails.claims.notification', [
            'claim' => $claim,
            'logoUrl' => $logoUrl,
            'claimEditUrl' => URL::to(ClaimResource::getUrl('edit', ['record' => $claim->id], panel: 'secure')),
        ]);
    }
}
