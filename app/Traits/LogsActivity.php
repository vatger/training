<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static $logActivityDisabled = false;

    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            if (!self::$logActivityDisabled) {
                $model->logActivity('created');
            }
        });

        static::updated(function ($model) {
            if (!self::$logActivityDisabled && $model->isDirty()) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function ($model) {
            if (!self::$logActivityDisabled) {
                $model->logActivity('deleted');
            }
        });
    }

    protected function logActivity(string $action)
    {
        if (!$this->shouldLogActivity($action)) {
            return;
        }

        $old = $action === 'updated' ? $this->getOriginal() : [];
        $new = $action !== 'deleted' ? $this->getAttributes() : [];

        ActivityLogger::logModelChange(
            $this->getActivityAction($action),
            $this,
            Auth::user(),
            $old,
            $new
        );
    }

    protected function shouldLogActivity(string $action): bool
    {
        if (property_exists($this, 'logOnly') && !in_array($action, $this->logOnly)) {
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
        return $this->morphMany(\App\Models\ActivityLog::class, 'model');
    }
}