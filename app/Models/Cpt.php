<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Cpt extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'cpts';

    protected $fillable = [
        'trainee_id',
        'examiner_id',
        'local_id',
        'course_id',
        'date',
        'passed',
        'confirmed',
        'log_uploaded',
    ];

    protected $casts = [
        'date' => 'datetime',
        'passed' => 'boolean',
        'confirmed' => 'boolean',
        'log_uploaded' => 'boolean',
    ];

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(User::class, 'local_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CptLog::class);
    }

    public function isConfirmed(): bool
    {
        return $this->examiner_id !== null && $this->local_id !== null;
    }

    public function isPending(): bool
    {
        return $this->passed === null;
    }

    public function scopePending($query)
    {
        return $query->whereNull('passed');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('confirmed', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>', now())->whereNull('passed');
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cpt) {
            $cpt->confirmed = $cpt->examiner_id !== null && $cpt->local_id !== null;
        });
    }
}