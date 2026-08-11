<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingListVerificationRun extends Model
{
    protected $fillable = [
        'year_month',
        'ran_at',
    ];

    protected $casts = [
        'ran_at' => 'datetime',
    ];
}
