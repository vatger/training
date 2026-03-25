<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingListRestriction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
