<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasBilingualStrings;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'title_en',
        'description',
        'description_en',
        'status',
        'duration_minutes',
        'pass_percent',
        'max_attempts',
        'shuffle_questions',
        'shuffle_options',
        'show_correct_answers',
        'available_from',
        'available_until',
    ];

    protected $appends = [
        'localized_title',
        'localized_description',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'pass_percent' => 'float',
        'max_attempts' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_correct_answers' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function getLocalizedTitleAttribute(): string
    {
        return $this->bilingualString('title', 'title_en');
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        return $this->bilingualString('description', 'description_en');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function gradeBands(): HasMany
    {
        return $this->hasMany(ExamGradeBand::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isAvailableNow(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }
}
