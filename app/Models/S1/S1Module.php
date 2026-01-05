<?php

namespace App\Models\S1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class S1Module extends Model
{
    protected $table = 's1_modules';

    protected $fillable = [
        'name',
        'sequence_order',
        'description',
        'moodle_course_ids',
        'moodle_quiz_ids',
        'is_active',
    ];

    protected $casts = [
        'moodle_course_ids' => 'array',
        'moodle_quiz_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function completions(): HasMany
    {
        return $this->hasMany(S1ModuleCompletion::class, 'module_id');
    }

    public function waitingLists(): HasMany
    {
        return $this->hasMany(S1WaitingList::class, 'module_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(S1Session::class, 'module_id');
    }

    public function activeWaitingLists(): HasMany
    {
        return $this->waitingLists()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }

    public function __toString(): string
    {
        return $this->name;
    }
}