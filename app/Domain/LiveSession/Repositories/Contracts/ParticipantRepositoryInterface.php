<?php

namespace App\Domain\LiveSession\Repositories\Contracts;

use App\Domain\LiveSession\DTOs\ParticipantDTO;
use App\Domain\LiveSession\Enums\ParticipantRole;
use App\Domain\LiveSession\Models\LiveSessionParticipant;
use Illuminate\Database\Eloquent\Collection;

interface ParticipantRepositoryInterface
{
    /**
     * Find a participant by ID.
     */
    public function findById(int $id): ?LiveSessionParticipant;

    /**
     * Create a new participant.
     */
    public function create(ParticipantDTO $dto, int $sessionId): LiveSessionParticipant;

    /**
     * Update an existing participant.
     */
    public function update(LiveSessionParticipant $participant, ParticipantDTO $dto): LiveSessionParticipant;

    /**
     * Delete a participant.
     */
    public function delete(LiveSessionParticipant $participant): bool;

    /**
     * Get participants for a session.
     */
    public function getBySession(int $sessionId): Collection;

    /**
     * Get active participants for a session.
     */
    public function getActiveBySession(int $sessionId): Collection;

    /**
     * Get participants by role for a session.
     */
    public function getBySessionAndRole(int $sessionId, ParticipantRole $role): Collection;

    /**
     * Get teachers for a session.
     */
    public function getTeachersBySession(int $sessionId): Collection;

    /**
     * Get students for a session.
     */
    public function getStudentsBySession(int $sessionId): Collection;

    /**
     * Find a participant by session and user.
     */
    public function findBySessionAndUser(int $sessionId, int $userId): ?LiveSessionParticipant;

    /**
     * Check if a user is an active participant.
     */
    public function isActiveParticipant(int $sessionId, int $userId): bool;

    /**
     * Set participant as left.
     */
    public function setLeft(LiveSessionParticipant $participant): bool;

    /**
     * Update connection quality.
     */
    public function updateConnectionQuality(LiveSessionParticipant $participant, string $quality): bool;

    /**
     * Count active participants for a session.
     */
    public function countActiveBySession(int $sessionId): int;

    /**
     * Get participant statistics for a session.
     */
    public function getStatistics(int $sessionId): array;
}
