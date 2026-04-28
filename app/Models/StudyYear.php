<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyYear extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'faculty_id',
        'year_number',
        'name',
        'name_en',
    ];

    protected $casts = [
        'year_number' => 'integer',
    ];

    protected $appends = [
        'localized_name',
    ];

    public function getLocalizedNameAttribute(): string
    {
        $label = $this->bilingualString('name', 'name_en');
        if (trim($label) !== '') {
            return $label;
        }

        return (string) $this->year_number;
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(StudyTerm::class);
    }
}

