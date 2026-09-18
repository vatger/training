<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function scopeMentorRoles($query)
    {
        return $query->whereIn('name', ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
    }

    public function scopeLeadershipRoles($query)
    {
        return $query->whereIn('name', ['ATD Leitung', 'VATGER Leitung']);
    }

    public function isMentorRole(): bool
    {
        return in_array($this->name, ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
    }

    public function isLeadershipRole(): bool
    {
        return in_array($this->name, ['ATD Leitung', 'VATGER Leitung']);
    }
}
