<?php

use App\Http\Controllers\MailController;
use App\Livewire\SubmitClaim;
use Illuminate\Support\Facades\Route;

Route::get('/', SubmitClaim::class);

Route::middleware(\Filament\Http\Middleware\Authenticate::class)
    ->prefix('email')
    ->group(function () {
        Route::get('/viewTemplate/{id}', [MailController::class, 'viewTemplate'])
            ->name('email.viewTemplate');
    });
