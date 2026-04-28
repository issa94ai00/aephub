<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'teacher_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'price_cents',
        'currency',
        'sham_cash_code',
        'status',
        'cover_image_disk',
        'cover_image_path',
    ];

    protected $appends = [
        'cover_image_url',
        'localized_title',
        'localized_description',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'cover_image_disk',
        'cover_image_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Course $course): void {
            if ($course->cover_image_path) {
                $disk = $course->cover_image_disk ?: 'local';
                if (Storage::disk($disk)->exists($course->cover_image_path)) {
                    Storage::disk($disk)->delete($course->cover_image_path);
                }
            }
        });
    }

    /**
     * Relative URL so API clients resolve it against their own base (avoid double base / wrong host).
     * Same-origin web and Inertia can use it as-is; Flutter: Uri.parse(apiBase).resolve(path).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        return '/api/v1/courses/'.$this->id.'/cover';
    }

    public function getLocalizedTitleAttribute(): string
    {
        return $this->bilingualString('title', 'title_en');
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        return $this->bilingualString('description', 'description_en');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CourseVideo::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class)->orderBy('sort_order')->orderBy('id');
    }

    public function studyTerms(): BelongsToMany
    {
        return $this->belongsToMany(StudyTerm::class, 'course_study_terms', 'course_id', 'study_term_id')
            ->withTimestamps();
    }

    public function files(): HasMany
    {
        return $this->hasMany(CourseFile::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(CourseChatMessage::class);
    }
}
