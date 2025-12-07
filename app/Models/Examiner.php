<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Examiner extends Model
{
    use HasFactory;

    const POSITION_TWR = 'TWR';
    const POSITION_APP = 'APP';
    const POSITION_CTR = 'CTR';

    const VALID_POSITIONS = [
        self::POSITION_TWR,
        self::POSITION_APP,
        self::POSITION_CTR,
    ];

    protected $fillable = [
        'user_id',
        'callsign',
        'positions',
    ];

    protected $casts = [
        'positions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPosition(string $position): bool
    {
        return in_array($position, $this->positions ?? []);
    }

    public function getPositionsDisplayAttribute(): string
    {
        if (empty($this->positions)) {
            return 'No positions';
        }

        return implode(', ', array_map(function ($pos) {
            return match($pos) {
                self::POSITION_TWR => 'Tower',
                self::POSITION_APP => 'Approach',
                self::POSITION_CTR => 'Centre',
                default => $pos,
            };
        }, $this->positions));
    }

    public function getFullDisplayAttribute(): string
    {
        return "{$this->user->full_name} - {$this->callsign} ({$this->positions_display})";
    }

    public function __toString(): string
    {
        return $this->full_display;
    }

    public static function getPositionLabel(string $position): string
    {
        return match($position) {
            self::POSITION_TWR => 'Tower',
            self::POSITION_APP => 'Approach',
            self::POSITION_CTR => 'Centre',
            default => $position,
        };
    }

    public static function getPositionOptions(): array
    {
        return [
            self::POSITION_TWR => 'Tower',
            self::POSITION_APP => 'Approach',
            self::POSITION_CTR => 'Centre',
        ];
    }
}