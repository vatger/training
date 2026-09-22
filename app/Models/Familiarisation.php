<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Familiarisation extends Model
{
    use LogsActivity;

    protected $fillable = ['user_id', 'familiarisation_sector_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(FamiliarisationSector::class, 'familiarisation_sector_id');
    }

    public function __toString(): string
    {
        return "{$this->user->name} - {$this->sector->name}";
    }
}
