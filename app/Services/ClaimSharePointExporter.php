<?php

namespace App\Services;

use App\Http\Controllers\SharePointController;
use App\Models\Claim;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClaimSharePointExporter
{
    public static function exportWithNotification(Claim $claim): bool
    {
        try {
            $success = (new SharePointController)->run($claim->id);

            if ($success) {
                Notification::make()
                    ->title('Įrašyta į Excel')
                    ->body('Užklausos duomenys pridėti į „Automatizacija Klientas“.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Statusas atnaujintas')
                    ->body('Nepavyko įrašyti į Excel — patikrinkite SharePoint nustatymus arba žurnalą.')
                    ->warning()
                    ->send();
            }

            return $success;
        } catch (Throwable $e) {
            Log::error('SharePoint export failed', [
                'claim_id' => $claim->id,
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Statusas atnaujintas')
                ->body('Nepavyko įrašyti į Excel. Klaida užregistruota žurnale.')
                ->warning()
                ->send();

            return false;
        }
    }
}
