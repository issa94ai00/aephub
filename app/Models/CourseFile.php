<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseFile extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'course_id',
        'uploader_id',
        'name',
        'name_en',
        'storage_disk',
        'storage_path',
        'size_bytes',
        'mime_type',
        'cipher',
        'content_key',
        'content_iv',
        'key_version',
        'encrypted_sha256',
    ];

    protected $appends = [
        'localized_name',
    ];

    public function getLocalizedNameAttribute(): string
    {
        return $this->bilingualString('name', 'name_en');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
