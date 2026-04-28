<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseSession extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'course_id',
        'title',
        'title_en',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'localized_title',
    ];

    public function getLocalizedTitleAttribute(): string
    {
        return $this->bilingualString('title', 'title_en');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(CourseVideo::class, 'course_session_videos', 'course_session_id', 'course_video_id')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderBy('course_session_videos.sort_order')
            ->orderBy('course_session_videos.id');
    }
}

