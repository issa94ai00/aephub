<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackSession extends Model
{
    protected $fillable = [
        'user_id',
        'course_video_id',
        'device_id',
        'status',
        'issued_at',
        'expires_at',
        'consumed_at',
        'ip',
        'user_agent',
        'watermark_text',
        'watermark_seed',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(CourseVideo::class, 'course_video_id');
    }
}
