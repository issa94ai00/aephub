<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'name',
        'name_en',
    ];

    protected $appends = [
        'localized_name',
    ];

    public function getLocalizedNameAttribute(): string
    {
        return $this->bilingualString('name', 'name_en');
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }
}

