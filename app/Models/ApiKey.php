<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key',
        'permissions',
        'is_active',
        'expires_at',
        'last_used_at',
        'last_used_ip',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'key',
    ];

    public $plainKey = null;

    public static function generateKey(): string
    {
        return 'tc_' . Str::random(60);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function recordUsage(string $ip): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    public function getMaskedKeyAttribute(): string
    {
        return 'tc_' . str_repeat('*', 60);
    }

    protected static function booted()
    {
        static::creating(function ($apiKey) {
            if (!$apiKey->key) {
                $plainKey = self::generateKey();
                $apiKey->plainKey = $plainKey;
                $apiKey->key = hash('sha256', $plainKey);
            } else {
                $apiKey->plainKey = $apiKey->key;
                $apiKey->key = hash('sha256', $apiKey->key);
            }
        });
    }

    public static function findByPlainKey(string $plainKey): ?self
    {
        $hashedKey = hash('sha256', $plainKey);
        return self::where('key', $hashedKey)->first();
    }
}