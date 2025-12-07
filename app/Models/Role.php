<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Many-to-many relationship with users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /**
     * Scope to get mentor roles
     */
    public function scopeMentorRoles($query)
    {
        return $query->whereIn('name', ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
    }

    /**
     * Scope to get leadership roles
     */
    public function scopeLeadershipRoles($query)
    {
        return $query->whereIn('name', ['ATD Leitung', 'VATGER Leitung']);
    }

    /**
     * Check if this role is a mentor role
     */
    public function isMentorRole(): bool
    {
        return in_array($this->name, ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
    }

    /**
     * Check if this role is a leadership role
     */
    public function isLeadershipRole(): bool
    {
        return in_array($this->name, ['ATD Leitung', 'VATGER Leitung']);
    }
}