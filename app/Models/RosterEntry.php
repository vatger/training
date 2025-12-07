<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterEntry extends Model
{
    protected $fillable = [
        'user_id',
        'last_session',
        'removal_date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'last_session' => 'datetime',
        'removal_date' => 'datetime',
    ];

    public function __toString(): string
    {
        return (string) $this->user_id;
    }
}