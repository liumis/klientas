<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ClaimStatus: string implements HasColor, HasLabel
{
    case REQUEST = 'uzklausa';
    case CONFIRMED = 'patvirtinta';
    case CANCELLED = 'atsaukta';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::REQUEST => 'Užklausa',
            self::CONFIRMED => 'Patvirtinta',
            self::CANCELLED => 'Atšaukta',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REQUEST => 'gray',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
