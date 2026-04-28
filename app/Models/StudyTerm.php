<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudyTerm extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'study_year_id',
        'term_number',
        'name',
        'name_en',
    ];

    protected $casts = [
        'term_number' => 'integer',
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

        return (string) $this->term_number;
    }

    public function studyYear(): BelongsTo
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_study_terms', 'study_term_id', 'course_id')
            ->withTimestamps();
    }
}

