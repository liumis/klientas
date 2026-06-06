<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class EmailSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_secret',
        'mail',
        'from_address',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
        ];
    }

    public static function current(): ?self
    {
        if (! Schema::hasTable('email_settings')) {
            return null;
        }

        return static::query()->first();
    }
}
