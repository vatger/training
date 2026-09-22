<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class WaitingListRestriction extends Model
{
    use LogsActivity;

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
