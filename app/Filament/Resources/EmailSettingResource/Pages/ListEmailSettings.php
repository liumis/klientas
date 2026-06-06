<?php

namespace App\Filament\Resources\EmailSettingResource\Pages;

use App\Filament\Resources\EmailSettingResource;
use App\Models\EmailSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailSettings extends ListRecords
{
    protected static string $resource = EmailSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => EmailSetting::query()->count() === 0),
        ];
    }
}
