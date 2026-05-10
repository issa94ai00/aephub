<?php

namespace App\Domain\LiveSession\Repositories\Contracts;

use App\Domain\LiveSession\DTOs\EventDTO;
use App\Domain\LiveSession\Enums\EventType;
use App\Domain\LiveSession\Models\LiveSessionEvent;
use Illuminate\Database\Eloquent\Collection;

interface EventRepositoryInterface
{
    /**
     * Find an event by ID.
     */
    public function findById(int $id): ?LiveSessionEvent;

    /**
     * Create a new event.
     */
    public function create(EventDTO $dto): LiveSessionEvent;

    /**
     * Create multiple events in bulk.
     */
    public function bulkCreate(array $events): bool;

    /**
     * Delete an event.
     */
    public function delete(LiveSessionEvent $event): bool;

    /**
     * Get events for a session.
     */
    public function getBySession(int $sessionId): Collection;

    /**
     * Get events for a session within a time range.
     */
    public function getBySessionAndTimestampRange(int $sessionId, int $from, int $to): Collection;

    /**
     * Get events after a timestamp for a session.
     */
    public function getBySessionAfterTimestamp(int $sessionId, int $timestamp): Collection;

    /**
     * Get events by type for a session.
     */
    public function getBySessionAndType(int $sessionId, EventType $type): Collection;

    /**
     * Get canvas events for a session.
     */
    public function getCanvasEventsBySession(int $sessionId): Collection;

    /**
     * Get events by user for a session.
     */
    public function getBySessionAndUser(int $sessionId, int $userId): Collection;

    /**
     * Count events for a session.
     */
    public function countBySession(int $sessionId): int;

    /**
     * Delete all events for a session.
     */
    public function deleteBySession(int $sessionId): bool;

    /**
     * Archive events for a session (move to cold storage).
     */
    public function archiveBySession(int $sessionId): bool;
}
