<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'status',
        'requested_at',
        'approved_at',
        'approved_by',
        'access_locked',
        'access_locked_at',
        'access_locked_by',
        'paid_amount_cents',
        'unlocked_videos_count',
        'unlocked_sessions_count',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'access_locked' => 'boolean',
        'access_locked_at' => 'datetime',
        'paid_amount_cents' => 'integer',
        'unlocked_videos_count' => 'integer',
        'unlocked_sessions_count' => 'integer',
    ];

    /**
     * Approved enrollment with no access suspension (can use course materials).
     */
    public function hasActiveCourseAccess(): bool
    {
        return $this->status === 'approved' && ! $this->access_locked;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
