<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static $logActivityDisabled = false;

    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            if (! self::$logActivityDisabled) {
                $model->logActivity('created');
            }
        });

        static::updated(function ($model) {
            if (! self::$logActivityDisabled && $model->isDirty()) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function ($model) {
            if (! self::$logActivityDisabled) {
                $model->logActivity('deleted');
            }
        });
    }

    protected function logActivity(string $action)
    {
        if (! $this->shouldLogActivity($action)) {
            return;
        }

        $old = $action === 'updated' ? $this->getOriginal() : [];
        $new = $action !== 'deleted' ? $this->getAttributes() : [];

        if ($action === 'updated') {
            $attributesToLog = property_exists($this, 'loggedAttributes') && is_array($this->loggedAttributes)
                ? $this->loggedAttributes
                : array_keys($this->getAttributes());

            $old = array_intersect_key($old, array_flip($attributesToLog));
            $new = array_intersect_key($new, array_flip($attributesToLog));

            // Nothing changed? Skip logging
            if (empty(array_diff_assoc($new, $old))) {
                return;
            }
        }

        $causer = Auth::user();
        $changes = [];
        foreach ($new as $key => $value) {
            if (($old[$key] ?? null) != $value) {
                $changes[$key] = ['old' => $old[$key] ?? null, 'new' => $value];
            }
        }
        $modelName = class_basename($this);
        $userName = $causer?->name ?? 'System';
        $description = empty($changes)
            ? "{$userName} {$action} {$modelName} #{$this->id}"
            : "{$userName} {$action} {$modelName} #{$this->id} (changed: ".implode(', ', array_keys($changes)).')';

        ActivityLog::create([
            'user_id' => $causer?->id ?? Auth::id(),
            'action' => $this->getActivityAction($action),
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'properties' => [
                'old' => $old,
                'new' => $new,
                'changes' => $changes,
                'causer_id' => $causer?->id,
                'causer_name' => $causer?->name,
            ],
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function shouldLogActivity(string $action): bool
    {
        if (property_exists($this, 'logOnly') && ! in_array($action, $this->logOnly)) {
            return false;
        }

        if (property_exists($this, 'logExcept') && in_array($action, $this->logExcept)) {
            return false;
        }

        return true;
    }

    protected function getActivityAction(string $action): string
    {
        $modelName = strtolower(class_basename($this));

        return "{$modelName}.{$action}";
    }

    public static function withoutLogging(callable $callback)
    {
        self::$logActivityDisabled = true;
        $result = $callback();
        self::$logActivityDisabled = false;

        return $result;
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
}
