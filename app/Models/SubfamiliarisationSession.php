<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubfamiliarisationSession extends Model
{
    use LogsActivity;

    protected $fillable = [
        'subfamiliarisation_id',
        'user_id',
        'mentor_id',
        'completed_at',
        'moodle_override',
        'remarks',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'moodle_override' => 'boolean',
    ];

    protected $loggedAttributes = [
        'subfamiliarisation_id',
        'user_id',
        'mentor_id',
        'completed_at',
        'moodle_override',
        'remarks',
    ];

    public function subfamiliarisation(): BelongsTo
    {
        return $this->belongsTo(Subfamiliarisation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function __toString(): string
    {
        return "{$this->user->name} - {$this->subfamiliarisation->name}";
    }
}
