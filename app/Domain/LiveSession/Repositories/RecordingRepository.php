<?php

namespace App\Domain\LiveSession\Repositories;

use App\Domain\LiveSession\DTOs\RecordingDTO;
use App\Domain\LiveSession\Enums\RecordingStatus;
use App\Domain\LiveSession\Models\LiveSessionRecording;
use App\Domain\LiveSession\Repositories\Contracts\RecordingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RecordingRepository implements RecordingRepositoryInterface
{
    public function findById(int $id): ?LiveSessionRecording
    {
        return LiveSessionRecording::find($id);
    }

    public function create(RecordingDTO $dto): LiveSessionRecording
    {
        return LiveSessionRecording::create(array_merge($dto->toArray(), [
            'status' => RecordingStatus::PROCESSING->value,
            'processing_started_at' => now(),
        ]));
    }

    public function update(LiveSessionRecording $recording, RecordingDTO $dto): LiveSessionRecording
    {
        $recording->update($dto->toArray());
        return $recording->fresh();
    }

    public function delete(LiveSessionRecording $recording): bool
    {
        return $recording->delete();
    }

    public function getBySession(int $sessionId): Collection
    {
        return LiveSessionRecording::where('session_id', $sessionId)->latest()->get();
    }

    public function getLatestBySession(int $sessionId): ?LiveSessionRecording
    {
        return LiveSessionRecording::where('session_id', $sessionId)->latest()->first();
    }

    public function getByStatus(RecordingStatus $status): Collection
    {
        return LiveSessionRecording::where('status', $status->value)->get();
    }

    public function getProcessing(): Collection
    {
        return LiveSessionRecording::processing()->get();
    }

    public function getReady(): Collection
    {
        return LiveSessionRecording::ready()->get();
    }

    public function getFailed(): Collection
    {
        return LiveSessionRecording::failed()->get();
    }

    public function updateStatus(LiveSessionRecording $recording, RecordingStatus $status): bool
    {
        return $recording->update(['status' => $status->value]);
    }

    public function setProcessingStarted(LiveSessionRecording $recording): bool
    {
        return $recording->update([
            'status' => RecordingStatus::PROCESSING->value,
            'processing_started_at' => now(),
        ]);
    }

    public function setProcessingEnded(LiveSessionRecording $recording, ?string $errorMessage = null): bool
    {
        $data = [
            'processing_ended_at' => now(),
        ];

        if ($errorMessage) {
            $data['status'] = RecordingStatus::FAILED->value;
            $data['error_message'] = $errorMessage;
        } else {
            $data['status'] = RecordingStatus::READY->value;
        }

        return $recording->update($data);
    }

    public function getOlderThan(\DateTimeInterface $date): Collection
    {
        return LiveSessionRecording::where('created_at', '<', $date)->get();
    }
}
