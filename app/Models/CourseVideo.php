<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseVideo extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'course_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'storage_disk',
        'storage_path',
        'size_bytes',
        'duration_seconds',
        'mime_type',
        'encryption_cipher',
        'encrypted_content_key',
        'content_iv',
        'key_version',
        'encrypted_sha256',
        'status',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'duration_seconds' => 'integer',
    ];

    protected $appends = [
        'localized_title',
        'localized_description',
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

    public function progressRows(): HasMany
    {
        return $this->hasMany(CourseVideoProgress::class, 'course_video_id');
    }
}
