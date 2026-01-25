<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S1ModuleCompletion extends Model
{
    use HasFactory;
    protected $table = 's1_module_completions';

    protected $fillable = [
        'user_id',
        'module_id',
        'completed_at',
        'completed_by_mentor_id',
        'was_reset',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'was_reset' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(S1Module::class, 'module_id');
    }

    public function completedByMentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_mentor_id');
    }
}