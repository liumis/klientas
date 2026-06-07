<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function viewTemplate($id, Request $request)
    {
        $claim = Claim::findOrFail($id);

        return view('emails.claims.notification', [
            'claim' => $claim,
        ]);
    }
}
