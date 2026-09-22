<?php

namespace App\Models;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'properties',
        'description',
        'ip_address',
        'user_agent',
        'is_admin_action',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'is_admin_action' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Record an admin-panel action that doesn't map to a single model's own
     * create/update/delete lifecycle (e.g. a relationship/pivot change made
     * via a bespoke Filament action), filling in the causer/request details.
     * Always marked as an admin action — see `is_admin_action`.
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $properties = []): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $subject ? get_class($subject) : null,
            'model_id' => $subject?->getKey(),
            'properties' => $properties,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'is_admin_action' => true,
        ]);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        // Admins see everything. Everyone else who can open the activity log
        // (superusers, or a leading mentor/chief of training granted the
        // permission) only ever sees the pre-existing domain-event log —
        // never the record of admin-panel changes, which is admin-only.
        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('is_admin_action', false);
    }

    public function getActionLabel(): string
    {
        $enum = ActivityAction::fromString($this->action);

        return $enum ? $enum->getLabel() : $this->action;
    }

    public function getActionColor(): string
    {
        $enum = ActivityAction::fromString($this->action);

        return $enum ? $enum->getColor() : 'info';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, string $modelType, int $modelId)
    {
        return $query->where('model_type', $modelType)
            ->where('model_id', $modelId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
