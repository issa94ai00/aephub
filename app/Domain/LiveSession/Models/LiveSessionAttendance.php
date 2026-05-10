<?php

namespace App\Domain\LiveSession\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSessionAttendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recording_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_ms',
        'completion_pct',
        'last_position_ms',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_ms' => 'integer',
        'completion_pct' => 'decimal:2',
        'last_position_ms' => 'integer',
    ];

    /**
     * Get the recording that the attendance belongs to.
     */
    public function recording(): BelongsTo
    {
        return $this->belongsTo(LiveSessionRecording::class, 'recording_id');
    }

    /**
     * Get the user who attended the recording.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if the attendance is currently active (watching).
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Check if the user has completed the recording.
     */
    public function isCompleted(): bool
    {
        return $this->completion_pct >= 100.00;
    }

    /**
     * Check if the user has partially watched the recording.
     */
    public function isPartial(): bool
    {
        return $this->completion_pct > 0 && $this->completion_pct < 100;
    }

    /**
     * Update the completion percentage based on duration.
     */
    public function updateCompletion(int $totalDurationMs): void
    {
        $this->completion_pct = min(100, ($this->duration_ms / $totalDurationMs) * 100);
        $this->save();
    }

    /**
     * Get the duration in human-readable format.
     */
    public function getHumanDurationAttribute(): string
    {
        $seconds = floor($this->duration_ms / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes % 60, $seconds % 60);
        }

        return sprintf('%d:%02d', $minutes, $seconds % 60);
    }

    /**
     * Scope a query to only include active attendance records.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope a query to only include completed attendance records.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completion_pct', '>=', 100);
    }

    /**
     * Scope a query to only include partial attendance records.
     */
    public function scopePartial($query)
    {
        return $query->where('completion_pct', '>', 0)->where('completion_pct', '<', 100);
    }

    /**
     * Scope a query to only include attendance records for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include attendance records within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }
}
