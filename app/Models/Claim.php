<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ClaimStatus;

class Claim extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'repair_vehicle_plates', 'personal_code', 'birth_date',
        'license_number', 'license_expires_at',
        'id_or_passport_number', 'id_or_passport_expires_at',
        'bank_card_number', 'bank_card_expires_at',
        'address',
        'phone', 'email', 'claim_number', 'documents', 'rental_start',
        'rental_end', 'status',
    ];

    protected $casts = [
        'documents' => 'array',
        'birth_date' => 'date',
        'license_expires_at' => 'date',
        'id_or_passport_expires_at' => 'date',
        'bank_card_expires_at' => 'date',
        'rental_start' => 'date',
        'rental_end' => 'date',
        'status' => ClaimStatus::class,
    ];
}
