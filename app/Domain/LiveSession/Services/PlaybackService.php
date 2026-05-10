<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\Models\LiveSessionRecording;
use App\Domain\LiveSession\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class PlaybackService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    ) {}

    /**
     * Get playback data for a recording.
     */
    public function getPlaybackData(LiveSessionRecording $recording): array
    {
        if (!$recording->canBePlayed()) {
            throw new \Exception('Recording is not ready for playback');
        }

        $session = $recording->session;
        $assets = $session->assets;

        return [
            'recording' => [
                'id' => $recording->id,
                'audio_url' => $recording->audio_url,
                'events_url' => $recording->events_url,
                'duration_ms' => $recording->duration_ms,
                'codec' => $recording->codec,
                'sample_rate' => $recording->sample_rate,
                'channels' => $recording->channels,
            ],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'teacher' => $session->teacher->name,
                'created_at' => $session->created_at,
            ],
            'assets' => $assets->map(fn ($asset) => [
                'id' => $asset->id,
                'type' => $asset->type->value,
                'file_name' => $asset->file_name,
                'download_url' => $asset->download_url,
                'thumbnail_url' => $asset->thumbnail_url,
                'page_count' => $asset->page_count,
            ])->toArray(),
        ];
    }

    /**
     * Load events for playback.
     */
    public function loadEvents(int $sessionId): string
    {
        return Storage::disk(config('live-session.storage.recordings_disk', 's3'))
            ->get($this->getEventsPath($sessionId));
    }

    /**
     * Get events for a specific time range during playback.
     */
    public function getEventsForPlayback(
        int $sessionId,
        int $fromMs,
        int $toMs,
    ): \Illuminate\Database\Eloquent\Collection {
        return $this->eventRepository->getBySessionAndTimestampRange($sessionId, $fromMs, $toMs);
    }

    /**
     * Get initial events for playback (first page).
     */
    public function getInitialEvents(int $sessionId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return $this->eventRepository->getBySession($sessionId)->take($limit);
    }

    /**
     * Calculate playback position based on audio timestamp.
     */
    public function calculatePositionMs(
        int $sessionId,
        float $audioSeconds,
    ): int {
        return (int) ($audioSeconds * 1000);
    }

    /**
     * Get the events path for a session.
     */
    private function getEventsPath(int $sessionId): string
    {
        $recording = app(\App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface::class)
            ->getLatestBySession($sessionId);

        if (!$recording) {
            throw new \Exception('No recording found for session');
        }

        return $recording->events_path;
    }

    /**
     * Sync events with audio position during playback.
     */
    public function syncEventsWithPosition(
        int $sessionId,
        int $positionMs,
        int $bufferMs = 1000,
    ): \Illuminate\Database\Eloquent\Collection {
        return $this->eventRepository->getBySessionAndTimestampRange(
            $sessionId,
            $positionMs - $bufferMs,
            $positionMs + $bufferMs,
        );
    }

    /**
     * Get playback statistics.
     */
    public function getPlaybackStatistics(int $recordingId): array
    {
        $recording = app(\App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface::class)
            ->findById($recordingId);

        if (!$recording) {
            throw new \Exception('Recording not found');
        }

        $attendance = $recording->attendance;
        $totalViews = $attendance->count();
        $completedViews = $attendance->completed()->count();
        $partialViews = $attendance->partial()->count();

        return [
            'total_views' => $totalViews,
            'completed_views' => $completedViews,
            'partial_views' => $partialViews,
            'completion_rate' => $totalViews > 0 ? round(($completedViews / $totalViews) * 100, 2) : 0,
            'average_watch_time_ms' => $totalViews > 0 ? $attendance->avg('duration_ms') : 0,
        ];
    }
}
