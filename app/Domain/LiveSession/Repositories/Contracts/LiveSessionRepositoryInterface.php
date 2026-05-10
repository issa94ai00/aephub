<?php

namespace App\Domain\LiveSession\Repositories\Contracts;

use App\Domain\LiveSession\DTOs\CreateLiveSessionDTO;
use App\Domain\LiveSession\DTOs\UpdateLiveSessionDTO;
use App\Domain\LiveSession\Enums\SessionStatus;
use App\Domain\LiveSession\Models\LiveSession;
use Illuminate\Pagination\LengthAwarePaginator;

interface LiveSessionRepositoryInterface
{
    /**
     * Find a session by ID.
     */
    public function findById(int $id): ?LiveSession;

    /**
     * Find a session by LiveKit room ID.
     */
    public function findByLiveKitRoomId(string $roomId): ?LiveSession;

    /**
     * Create a new session.
     */
    public function create(CreateLiveSessionDTO $dto): LiveSession;

    /**
     * Update an existing session.
     */
    public function update(LiveSession $session, UpdateLiveSessionDTO $dto): LiveSession;

    /**
     * Delete a session.
     */
    public function delete(LiveSession $session): bool;

    /**
     * Get paginated sessions for a course.
     */
    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get paginated sessions for a teacher.
     */
    public function paginateByTeacher(int $teacherId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get sessions by status.
     */
    public function getByStatus(SessionStatus $status): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get live sessions.
     */
    public function getLiveSessions(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get scheduled sessions.
     */
    public function getScheduledSessions(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get upcoming sessions for a course.
     */
    public function getUpcomingForCourse(int $courseId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Update session status.
     */
    public function updateStatus(LiveSession $session, SessionStatus $status): bool;

    /**
     * Set LiveKit room ID.
     */
    public function setLiveKitRoomId(LiveSession $session, string $roomId): bool;

    /**
     * Update session timestamps.
     */
    public function updateTimestamps(LiveSession $session, ?\DateTime $startedAt = null, ?\DateTime $endedAt = null): bool;
}
