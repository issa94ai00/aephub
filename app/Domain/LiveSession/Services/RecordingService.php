<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\DTOs\RecordingDTO;
use App\Domain\LiveSession\Enums\RecordingStatus;
use App\Domain\LiveSession\Models\LiveSessionRecording;
use App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface;
use App\Services\External\LiveKit\LiveKitRoomManager;
use Illuminate\Support\Facades\Storage;

class RecordingService
{
    public function __construct(
        private readonly RecordingRepositoryInterface $repository,
        private readonly LiveKitRoomManager $roomManager,
    ) {}

    /**
     * Start recording for a session.
     */
    public function startRecording(
        int $sessionId,
        string $roomName,
        string $outputPath,
    ): array {
        $recording = $this->repository->create(new RecordingDTO(
            storageDisk: config('live-session.storage.recordings_disk', 's3'),
            audioPath: $outputPath . '.opus',
            eventsPath: $outputPath . '_events.json',
            durationMs: 0,
            audioSizeBytes: 0,
            eventsSizeBytes: 0,
            codec: 'opus',
            sampleRate: config('livekit.audio.default_sample_rate', 16000),
            channels: config('livekit.audio.default_channels', 1),
            bitrateKbps: config('livekit.audio.default_bitrate', 32),
        ));

        // Start LiveKit recording
        $livekitRecording = $this->roomManager->startRecording($roomName, $outputPath);

        return [
            'recording' => $recording,
            'livekit_egress_id' => $livekitRecording['egress_id'] ?? null,
        ];
    }

    /**
     * Stop recording and process it.
     */
    public function stopRecording(int $recordingId, string $egressId): bool
    {
        $recording = $this->repository->findById($recordingId);
        if (!$recording) {
            return false;
        }

        // Stop LiveKit recording
        $this->roomManager->stopRecording($egressId);

        // Mark as processing started
        $this->repository->setProcessingStarted($recording);

        // Dispatch job to process recording
        dispatch(new \App\Domain\LiveSession\Jobs\ProcessRecordingJob($recordingId));

        return true;
    }

    /**
     * Process recording (compress audio, export events).
     */
    public function processRecording(int $recordingId): bool
    {
        $recording = $this->repository->findById($recordingId);
        if (!$recording) {
            return false;
        }

        try {
            $session = $recording->session;

            // Export events to JSON
            $eventsJson = app(EventService::class)->exportToJson($session->id);
            $eventsPath = $this->storeEventsJson($recording->storage_disk, $recording->events_path, $eventsJson);

            // Get audio file size
            $audioSize = Storage::disk($recording->storage_disk)->size($recording->audio_path);

            // Get events file size
            $eventsSize = Storage::disk($recording->storage_disk)->size($eventsPath);

            // Update recording with final data
            $this->repository->update($recording, new RecordingDTO(
                storageDisk: $recording->storage_disk,
                audioPath: $recording->audio_path,
                eventsPath: $eventsPath,
                durationMs: $recording->duration_ms,
                audioSizeBytes: $audioSize,
                eventsSizeBytes: $eventsSize,
                codec: $recording->codec,
                sampleRate: $recording->sample_rate,
                channels: $recording->channels,
                bitrateKbps: $recording->bitrate_kbps,
            ));

            // Mark as ready
            $this->repository->setProcessingEnded($recording);

            return true;
        } catch (\Exception $e) {
            $this->repository->setProcessingEnded($recording, $e->getMessage());
            return false;
        }
    }

    /**
     * Get recording for a session.
     */
    public function getBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySession($sessionId);
    }

    /**
     * Get the latest recording for a session.
     */
    public function getLatestBySession(int $sessionId): ?LiveSessionRecording
    {
        return $this->repository->getLatestBySession($sessionId);
    }

    /**
     * Get recording by ID.
     */
    public function findById(int $id): ?LiveSessionRecording
    {
        return $this->repository->findById($id);
    }

    /**
     * Delete a recording.
     */
    public function delete(LiveSessionRecording $recording): bool
    {
        // Delete files from storage
        Storage::disk($recording->storage_disk)->delete($recording->audio_path);
        Storage::disk($recording->storage_disk)->delete($recording->events_path);

        // Delete record
        return $this->repository->delete($recording);
    }

    /**
     * Get processing recordings.
     */
    public function getProcessing(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getProcessing();
    }

    /**
     * Get failed recordings for retry.
     */
    public function getFailed(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getFailed();
    }

    /**
     * Store events JSON to storage.
     */
    private function storeEventsJson(string $disk, string $path, string $json): string
    {
        Storage::disk($disk)->put($path, $json);
        return $path;
    }
}
