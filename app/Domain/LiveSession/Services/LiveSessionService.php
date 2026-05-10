<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\DTOs\CreateLiveSessionDTO;
use App\Domain\LiveSession\DTOs\EndSessionDTO;
use App\Domain\LiveSession\DTOs\StartSessionDTO;
use App\Domain\LiveSession\DTOs\UpdateLiveSessionDTO;
use App\Domain\LiveSession\Enums\SessionStatus;
use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Repositories\Contracts\LiveSessionRepositoryInterface;
use App\Services\External\LiveKit\LiveKitRoomManager;
use Illuminate\Support\Facades\DB;

class LiveSessionService
{
    public function __construct(
        private readonly LiveSessionRepositoryInterface $repository,
        private readonly LiveKitRoomManager $roomManager,
    ) {}

    /**
     * Create a new live session.
     */
    public function create(CreateLiveSessionDTO $dto): LiveSession
    {
        return DB::transaction(function () use ($dto) {
            $session = $this->repository->create($dto);

            // Create LiveKit room
            $roomName = $this->roomManager->generateRoomName($session->id);
            $this->roomManager->createRoom($roomName, $dto->maxParticipants);

            // Update session with room ID
            $this->repository->setLiveKitRoomId($session, $roomName);

            return $session->fresh();
        });
    }

    /**
     * Update a live session.
     */
    public function update(LiveSession $session, UpdateLiveSessionDTO $dto): LiveSession
    {
        return $this->repository->update($session, $dto);
    }

    /**
     * Delete a live session.
     */
    public function delete(LiveSession $session): bool
    {
        return DB::transaction(function () use ($session) {
            // Delete LiveKit room if exists
            if ($session->livekit_room_id) {
                $this->roomManager->deleteRoom($session->livekit_room_id);
            }

            return $this->repository->delete($session);
        });
    }

    /**
     * Start a live session.
     */
    public function start(LiveSession $session, StartSessionDTO $dto): array
    {
        if (!$session->canStart()) {
            throw new \Exception('Session cannot be started in current status');
        }

        return DB::transaction(function () use ($session, $dto) {
            // Update session status and timestamps
            $this->repository->updateStatus($session, SessionStatus::LIVE);
            $this->repository->updateTimestamps($session, startedAt: now());

            // Generate teacher token
            $token = $this->roomManager->generateTeacherToken(
                roomName: $session->livekit_room_id,
                userId: $session->teacher_id,
                name: $session->teacher->name,
                ttl: config('livekit.token.ttl', 7200),
            );

            return [
                'session' => $session->fresh(),
                'token' => $token,
                'livekit_url' => $this->getLiveKitUrl(),
            ];
        });
    }

    /**
     * End a live session.
     */
    public function end(LiveSession $session, EndSessionDTO $dto): array
    {
        if (!$session->canEnd()) {
            throw new \Exception('Session cannot be ended in current status');
        }

        return DB::transaction(function () use ($session, $dto) {
            // Update session status and timestamps
            $this->repository->updateStatus($session, SessionStatus::ENDED);
            $this->repository->updateTimestamps($session, endedAt: now());

            // Stop recording if enabled
            if ($session->settings['recording_enabled'] ?? false) {
                // Recording would be stopped here
                // This would trigger a job to process the recording
            }

            return [
                'session' => $session->fresh(),
                'duration_ms' => $session->duration_ms,
            ];
        });
    }

    /**
     * Cancel a scheduled session.
     */
    public function cancel(LiveSession $session): LiveSession
    {
        if (!$session->status->canStart()) {
            throw new \Exception('Session cannot be cancelled in current status');
        }

        $this->repository->updateStatus($session, SessionStatus::CANCELLED);
        return $session->fresh();
    }

    /**
     * Get a token for a participant.
     */
    public function getParticipantToken(LiveSession $session, int $userId, string $role = 'student'): array
    {
        if (!$session->isLive()) {
            throw new \Exception('Session is not currently live');
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $token = $role === 'teacher'
            ? $this->roomManager->generateTeacherToken($session->livekit_room_id, $userId, $user->name)
            : $this->roomManager->generateStudentToken($session->livekit_room_id, $userId, $user->name);

        return [
            'token' => $token,
            'livekit_url' => $this->getLiveKitUrl(),
            'room_name' => $session->livekit_room_id,
            'participant' => [
                'identity' => "user_{$userId}",
                'name' => $user->name,
                'metadata' => json_encode([
                    'role' => $role,
                    'user_id' => $userId,
                ]),
            ],
        ];
    }

    /**
     * Get live sessions.
     */
    public function getLiveSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getLiveSessions();
    }

    /**
     * Get upcoming sessions for a course.
     */
    public function getUpcomingForCourse(int $courseId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getUpcomingForCourse($courseId);
    }

    /**
     * Get the LiveKit URL.
     */
    private function getLiveKitUrl(): string
    {
        $scheme = config('livekit.use_ssl', true) ? 'wss' : 'ws';
        return sprintf('%s://%s:%d', $scheme, config('livekit.host'), config('livekit.port'));
    }
}
