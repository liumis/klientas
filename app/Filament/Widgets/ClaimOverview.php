<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Enums\ClaimStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClaimOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Naujos užklausos', Claim::where('status', ClaimStatus::REQUEST)->count())
                ->description('Laukia peržiūros')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray'),

            Stat::make('Aktyvi nuoma', Claim::where('status', ClaimStatus::RENT_ACTIVE)->count())
                ->description('Vykdomi užsakymai')
                ->color('primary'),

            Stat::make('Laukia pasirašymo', Claim::whereIn('status', [ClaimStatus::READY, ClaimStatus::SETTLEMENT_SIGNED])->count())
                ->description('Ruošiami dokumentai')
                ->color('warning'),
                
            Stat::make('Iš viso užbaigta', Claim::where('status', ClaimStatus::COMPLETED)->count())
                ->description('Sėkmingi sandoriai')
                ->color('success'),
        ];
    }
}