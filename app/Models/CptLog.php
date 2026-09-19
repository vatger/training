<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CptLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpt_id',
        'uploaded_by_id',
        'log_file',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function cpt(): BelongsTo
    {
        return $this->belongsTo(Cpt::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function getFileNameAttribute(): string
    {
        return basename($this->log_file);
    }

    public function getFileUrlAttribute(): string
    {
        return route('cpt.log.view', $this->id);
    }

    /**
     * Delete the underlying log file from whichever disk it's actually stored on.
     */
    public function deleteFile(): void
    {
        if (Storage::disk('private')->exists($this->log_file)) {
            Storage::disk('private')->delete($this->log_file);
        } elseif (Storage::disk('public')->exists($this->log_file)) {
            Storage::disk('public')->delete($this->log_file);
        }
    }
}
