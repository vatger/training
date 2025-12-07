<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamiliarisationSector extends Model
{
    protected $fillable = ['name', 'fir'];

    protected $casts = [
        'fir' => 'string',
    ];

    public function familiarisations(): HasMany
    {
        return $this->hasMany(Familiarisation::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function __toString(): string
    {
        return "{$this->name} - {$this->fir}";
    }
}