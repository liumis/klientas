<?php

namespace App\Filament\Resources\ClaimResource\Pages;

use App\Filament\Resources\ClaimResource;
use App\Enums\ClaimStatus;
use App\Services\ClaimSharePointExporter;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (
            $this->record->wasChanged('status')
            && $this->record->status === ClaimStatus::CONFIRMED
        ) {
            ClaimSharePointExporter::exportWithNotification($this->record);
        }
    }
}
