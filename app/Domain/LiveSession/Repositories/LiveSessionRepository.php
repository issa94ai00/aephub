<?php

namespace App\Domain\LiveSession\Repositories;

use App\Domain\LiveSession\DTOs\CreateLiveSessionDTO;
use App\Domain\LiveSession\DTOs\UpdateLiveSessionDTO;
use App\Domain\LiveSession\Enums\SessionStatus;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Repositories\Contracts\LiveSessionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class LiveSessionRepository implements LiveSessionRepositoryInterface
{
    public function findById(int $id): ?LiveSession
    {
        return LiveSession::find($id);
    }

    public function findByLiveKitRoomId(string $roomId): ?LiveSession
    {
        return LiveSession::where('livekit_room_id', $roomId)->first();
    }

    public function create(CreateLiveSessionDTO $dto): LiveSession
    {
        return LiveSession::create($dto->toArray());
    }

    public function update(LiveSession $session, UpdateLiveSessionDTO $dto): LiveSession
    {
        $session->update($dto->toArray());
        return $session->fresh();
    }

    public function delete(LiveSession $session): bool
    {
        return $session->delete();
    }

    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        return LiveSession::forCourse($courseId)
            ->latest('scheduled_at')
            ->paginate($perPage);
    }

    public function paginateByTeacher(int $teacherId, int $perPage = 15): LengthAwarePaginator
    {
        return LiveSession::forTeacher($teacherId)
            ->latest('scheduled_at')
            ->paginate($perPage);
    }

    public function getByStatus(SessionStatus $status): \Illuminate\Database\Eloquent\Collection
    {
        return LiveSession::where('status', $status->value)->get();
    }

    public function getLiveSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return LiveSession::live()->get();
    }

    public function getScheduledSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return LiveSession::scheduled()->get();
    }

    public function getUpcomingForCourse(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        return LiveSession::forCourse($courseId)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->get();
    }

    public function updateStatus(LiveSession $session, SessionStatus $status): bool
    {
        return $session->update(['status' => $status->value]);
    }

    public function setLiveKitRoomId(LiveSession $session, string $roomId): bool
    {
        return $session->update(['livekit_room_id' => $roomId]);
    }

    public function updateTimestamps(LiveSession $session, ?\DateTime $startedAt = null, ?\DateTime $endedAt = null): bool
    {
        $data = [];
        if ($startedAt !== null) {
            $data['started_at'] = $startedAt;
        }
        if ($endedAt !== null) {
            $data['ended_at'] = $endedAt;
        }
        return $session->update($data);
    }
}
