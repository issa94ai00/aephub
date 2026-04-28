<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseVideoProgress extends Model
{
    protected $table = 'course_video_progress';

    protected $fillable = [
        'user_id',
        'course_video_id',
        'position_ms',
        'completed',
    ];

    protected $casts = [
        'position_ms' => 'integer',
        'completed' => 'boolean',
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
