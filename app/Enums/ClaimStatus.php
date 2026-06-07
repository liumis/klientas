<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ClaimStatus: string implements HasColor, HasLabel
{
    case REQUEST = 'uzklausa';
    case READY = 'paruosta';
    case SIGNED = 'pasirasyta';
    case RENT_ACTIVE = 'nuoma_aktyvi';
    case CAR_RETURNED = 'auto_grazintas';
    case SETTLEMENT_SIGNED = 'atsiskaitymas_pasirasytas';
    case COMPLETED = 'uzbaigta';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::REQUEST => 'Užklausa',
            self::READY => 'Paruošta',
            self::SIGNED => 'Pasirašyta',
            self::RENT_ACTIVE => 'Nuoma aktyvi',
            self::CAR_RETURNED => 'Auto grąžintas',
            self::SETTLEMENT_SIGNED => 'Atsiskaitymo prašymas pasirašytas',
            self::COMPLETED => 'Užbaigta',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REQUEST => 'gray',
            self::READY => 'warning',
            self::SIGNED => 'info',
            self::RENT_ACTIVE => 'primary',
            self::CAR_RETURNED => 'violet',
            self::SETTLEMENT_SIGNED => 'teal',
            self::COMPLETED => 'success',
        };
    }
}
