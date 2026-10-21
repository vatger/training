<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subfamiliarisation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'primary_sector_id',
        'secondary_sector_id',
        'moodle_course_ids',
    ];

    protected $casts = [
        'moodle_course_ids' => 'array',
    ];

    protected $loggedAttributes = [
        'name',
        'primary_sector_id',
        'secondary_sector_id',
        'moodle_course_ids',
    ];

    public function primarySector(): BelongsTo
    {
        return $this->belongsTo(FamiliarisationSector::class, 'primary_sector_id');
    }

    public function secondarySector(): BelongsTo
    {
        return $this->belongsTo(FamiliarisationSector::class, 'secondary_sector_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SubfamiliarisationSession::class);
    }

    public function sectorIds(): array
    {
        return [$this->primary_sector_id, $this->secondary_sector_id];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
