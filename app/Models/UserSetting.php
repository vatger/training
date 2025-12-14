<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'english_only',
        'notification_preferences',
    ];

    protected $casts = [
        'english_only' => 'boolean',
        'notification_preferences' => 'array',
    ];

    protected $attributes = [
        'theme' => 'system',
        'english_only' => false,
        'notification_preferences' => '{}',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNotificationPreference(string $type): bool
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences[$type] ?? true;
    }

    public function setNotificationPreference(string $type, bool $enabled): void
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$type] = $enabled;
        $this->notification_preferences = $preferences;
    }
}