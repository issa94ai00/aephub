<?php

namespace App\Domain\LiveSession\Repositories\Contracts;

use App\Domain\LiveSession\DTOs\RecordingDTO;
use App\Domain\LiveSession\Enums\RecordingStatus;
use App\Domain\LiveSession\Models\LiveSessionRecording;
use Illuminate\Database\Eloquent\Collection;

interface RecordingRepositoryInterface
{
    /**
     * Find a recording by ID.
     */
    public function findById(int $id): ?LiveSessionRecording;

    /**
     * Create a new recording.
     */
    public function create(RecordingDTO $dto): LiveSessionRecording;

    /**
     * Update an existing recording.
     */
    public function update(LiveSessionRecording $recording, RecordingDTO $dto): LiveSessionRecording;

    /**
     * Delete a recording.
     */
    public function delete(LiveSessionRecording $recording): bool;

    /**
     * Get recordings for a session.
     */
    public function getBySession(int $sessionId): Collection;

    /**
     * Get the latest recording for a session.
     */
    public function getLatestBySession(int $sessionId): ?LiveSessionRecording;

    /**
     * Get recordings by status.
     */
    public function getByStatus(RecordingStatus $status): Collection;

    /**
     * Get processing recordings.
     */
    public function getProcessing(): Collection;

    /**
     * Get ready recordings.
     */
    public function getReady(): Collection;

    /**
     * Get failed recordings.
     */
    public function getFailed(): Collection;

    /**
     * Update recording status.
     */
    public function updateStatus(LiveSessionRecording $recording, RecordingStatus $status): bool;

    /**
     * Set processing timestamps.
     */
    public function setProcessingStarted(LiveSessionRecording $recording): bool;

    /**
     * Set processing ended.
     */
    public function setProcessingEnded(LiveSessionRecording $recording, ?string $errorMessage = null): bool;

    /**
     * Get recordings older than a date.
     */
    public function getOlderThan(\DateTimeInterface $date): Collection;
}
