<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\MarkSignCallbackController;
use App\Livewire\SubmitClaim;
use Illuminate\Support\Facades\Route;
use App\Models\Claim;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', SubmitClaim::class);
Route::post('/marksign/callback', [MarkSignCallbackController::class, 'handle'])->name('marksign.callback');

Route::prefix('pdf-debug/{id}')->group(function () {

    Route::get('/html', function ($id) {
        $claim = Claim::with(['partner', 'garage'])->findOrFail($id);

        return view('pdf.claim', ['claim' => $claim]);
    });

    Route::get('/pdf', function ($id) {
        $claim = Claim::with(['partner', 'garage'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.claim', ['claim' => $claim]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("claim-{$id}.pdf");
    });
})->middleware(\Filament\Http\Middleware\Authenticate::class);

Route::middleware(\Filament\Http\Middleware\Authenticate::class)
    ->prefix('email')
    ->group(function () {
        Route::get('/viewTemplate/{id}', [MailController::class, 'viewTemplate'])
            ->name('email.viewTemplate');
    });
