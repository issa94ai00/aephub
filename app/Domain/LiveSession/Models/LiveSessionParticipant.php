<?php

namespace App\Domain\LiveSession\Models;

use App\Domain\LiveSession\Enums\ParticipantRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSessionParticipant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'joined_at',
        'left_at',
        'role',
        'ip_address',
        'user_agent',
        'connection_quality',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'role' => ParticipantRole::class,
    ];

    /**
     * Get the session that the participant belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    /**
     * Get the user that is the participant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if the participant is currently in the session.
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Get the duration in milliseconds.
     */
    public function getDurationMsAttribute(): ?int
    {
        if (!$this->joined_at) {
            return null;
        }

        $endTime = $this->left_at ?? now();
        return $this->joined_at->diffInMilliseconds($endTime);
    }

    /**
     * Get the duration in human-readable format.
     */
    public function getHumanDurationAttribute(): string
    {
        $duration = $this->duration_ms;

        if ($duration === null) {
            return '—';
        }

        $seconds = floor($duration / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes % 60, $seconds % 60);
        }

        return sprintf('%d:%02d', $minutes, $seconds % 60);
    }

    /**
     * Check if the participant is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === ParticipantRole::TEACHER;
    }

    /**
     * Check if the participant is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === ParticipantRole::STUDENT;
    }

    /**
     * Check if the participant is a guest.
     */
    public function isGuest(): bool
    {
        return $this->role === ParticipantRole::GUEST;
    }

    /**
     * Scope a query to only include active participants.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    /**
     * Scope a query to only include teachers.
     */
    public function scopeTeacher($query)
    {
        return $query->where('role', ParticipantRole::TEACHER->value);
    }

    /**
     * Scope a query to only include students.
     */
    public function scopeStudent($query)
    {
        return $query->where('role', ParticipantRole::STUDENT->value);
    }

    /**
     * Scope a query to only include guests.
     */
    public function scopeGuest($query)
    {
        return $query->where('role', ParticipantRole::GUEST->value);
    }
}
