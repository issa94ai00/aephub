<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'university_id',
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

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function studyYears(): HasMany
    {
        return $this->hasMany(StudyYear::class);
    }
}

