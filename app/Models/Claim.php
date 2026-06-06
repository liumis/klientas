<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ClaimStatus;

class Claim extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'personal_code', 'birth_date',
        'license_number', 'license_expires_at', 'address', 'partner_id',
        'phone', 'email', 'claim_number', 'documents','rental_start',
        'rental_end','garage_id','marksign_uuid',
        'signing_url','signed_pdf_path',
        'status',
    ];
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }
    protected $casts = [
        'documents' => 'array',
        'birth_date' => 'date',
        'license_expires_at' => 'date',
        'rental_start' => 'date',
        'rental_end' => 'date',
        'status' => ClaimStatus::class,
    ];

}
