<?php

namespace App\Filament\Widgets;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClaimOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Užklausos', Claim::where('status', ClaimStatus::REQUEST)->count())
                ->description('Laukia patvirtinimo')
                ->color('gray'),

            Stat::make('Patvirtinta', Claim::where('status', ClaimStatus::CONFIRMED)->count())
                ->description('Įrašyta į Excel')
                ->color('success'),

            Stat::make('Atšaukta', Claim::where('status', ClaimStatus::CANCELLED)->count())
                ->description('Atmestos užklausos')
                ->color('danger'),
        ];
    }
}
