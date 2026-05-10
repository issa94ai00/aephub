<?php

namespace App\Domain\LiveSession\Models;

use App\Domain\LiveSession\Enums\SessionStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'lesson_id',
        'teacher_id',
        'title',
        'description',
        'scheduled_at',
        'started_at',
        'ended_at',
        'status',
        'livekit_room_id',
        'max_participants',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'status' => SessionStatus::class,
        'settings' => 'array',
        'max_participants' => 'integer',
    ];

    /**
     * Get the course that owns the session.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lesson associated with the session.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'lesson_id');
    }

    /**
     * Get the teacher that owns the session.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the assets for the session.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(LiveSessionAsset::class, 'session_id');
    }

    /**
     * Get the participants for the session.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(LiveSessionParticipant::class, 'session_id');
    }

    /**
     * Get the events for the session.
     */
    public function events(): HasMany
    {
        return $this->hasMany(LiveSessionEvent::class, 'session_id')->orderBy('timestamp_ms');
    }

    /**
     * Get the recordings for the session.
     */
    public function recordings(): HasMany
    {
        return $this->hasMany(LiveSessionRecording::class, 'session_id');
    }

    /**
     * Get the current participant count.
     */
    public function getCurrentParticipantsAttribute(): int
    {
        return $this->participants()->whereNull('left_at')->count();
    }

    /**
     * Get the duration in milliseconds.
     */
    public function getDurationMsAttribute(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMilliseconds($this->ended_at);
    }

    /**
     * Check if the session is currently live.
     */
    public function isLive(): bool
    {
        return $this->status === SessionStatus::LIVE;
    }

    /**
     * Check if the session can be started.
     */
    public function canStart(): bool
    {
        return $this->status->canStart();
    }

    /**
     * Check if the session can be ended.
     */
    public function canEnd(): bool
    {
        return $this->status->canEnd();
    }

    /**
     * Check if the session has ended.
     */
    public function hasEnded(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Scope a query to only include scheduled sessions.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', SessionStatus::SCHEDULED->value);
    }

    /**
     * Scope a query to only include live sessions.
     */
    public function scopeLive($query)
    {
        return $query->where('status', SessionStatus::LIVE->value);
    }

    /**
     * Scope a query to only include ended sessions.
     */
    public function scopeEnded($query)
    {
        return $query->where('status', SessionStatus::ENDED->value);
    }

    /**
     * Scope a query to only include sessions for a specific course.
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope a query to only include sessions for a specific teacher.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Scope a query to only include upcoming sessions.
     */
    public function scopeUpcoming($query)
    {
        return $query->scheduled()->where('scheduled_at', '>', now());
    }

    /**
     * Scope a query to only include past sessions.
     */
    public function scopePast($query)
    {
        return $query->ended();
    }
}
