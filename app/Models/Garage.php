<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = [
        'name',
        'wheels_agent',
        'wheels_source',
    ];
}
