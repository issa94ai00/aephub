<?php

namespace App\Domain\LiveSession\Models;

use App\Domain\LiveSession\Enums\RecordingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSessionRecording extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'storage_disk',
        'audio_path',
        'events_path',
        'duration_ms',
        'audio_size_bytes',
        'events_size_bytes',
        'codec',
        'sample_rate',
        'channels',
        'bitrate_kbps',
        'status',
        'processing_started_at',
        'processing_ended_at',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_ms' => 'integer',
        'audio_size_bytes' => 'integer',
        'events_size_bytes' => 'integer',
        'sample_rate' => 'integer',
        'channels' => 'integer',
        'bitrate_kbps' => 'integer',
        'status' => RecordingStatus::class,
        'processing_started_at' => 'datetime',
        'processing_ended_at' => 'datetime',
    ];

    /**
     * Get the session that owns the recording.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    /**
     * Get the attendance records for the recording.
     */
    public function attendance(): HasMany
    {
        return $this->hasMany(LiveSessionAttendance::class, 'recording_id');
    }

    /**
     * Get the audio URL for the recording.
     */
    public function getAudioUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->storage_disk)
            ->temporaryUrl($this->audio_path, now()->addHours(24));
    }

    /**
     * Get the events URL for the recording.
     */
    public function getEventsUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->storage_disk)
            ->temporaryUrl($this->events_path, now()->addHours(24));
    }

    /**
     * Get the total file size in bytes.
     */
    public function getTotalSizeBytesAttribute(): int
    {
        return $this->audio_size_bytes + $this->events_size_bytes;
    }

    /**
     * Get the total file size in human-readable format.
     */
    public function getHumanTotalSizeAttribute(): string
    {
        $bytes = $this->total_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
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
     * Check if the recording is ready.
     */
    public function isReady(): bool
    {
        return $this->status === RecordingStatus::READY;
    }

    /**
     * Check if the recording is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === RecordingStatus::PROCESSING;
    }

    /**
     * Check if the recording failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === RecordingStatus::FAILED;
    }

    /**
     * Check if the recording can be played.
     */
    public function canBePlayed(): bool
    {
        return $this->status->canBePlayed();
    }

    /**
     * Scope a query to only include ready recordings.
     */
    public function scopeReady($query)
    {
        return $query->where('status', RecordingStatus::READY->value);
    }

    /**
     * Scope a query to only include processing recordings.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', RecordingStatus::PROCESSING->value);
    }

    /**
     * Scope a query to only include failed recordings.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', RecordingStatus::FAILED->value);
    }
}
