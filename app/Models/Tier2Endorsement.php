<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Tier2Endorsement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'moodle_course_id',
    ];

    protected $casts = [
        'moodle_course_id' => 'integer',
    ];

    /**
     * Check if this is a mentor role
     */
    public function isMentorEndorsement(): bool
    {
        return in_array($this->name, [
            'EDGG Mentor', 
            'EDMM Mentor', 
            'EDWW Mentor'
        ]);
    }

    /**
     * Check if this is a leadership role
     */
    public function isLeadershipEndorsement(): bool
    {
        return in_array($this->name, [
            'ATD Leitung', 
            'VATGER Leitung'
        ]);
    }
}