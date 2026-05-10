<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\DTOs\EventDTO;
use App\Domain\LiveSession\Models\LiveSessionEvent;
use App\Domain\LiveSession\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $repository,
    ) {}

    /**
     * Create a single event.
     */
    public function create(EventDTO $dto): LiveSessionEvent
    {
        $event = $this->repository->create($dto);

        // Broadcast via WebSocket
        $this->broadcastEvent($event);

        // Cache for quick access
        $this->cacheEvent($event);

        return $event;
    }

    /**
     * Create multiple events in bulk.
     */
    public function bulkCreate(array $events): bool
    {
        $dtos = array_map(fn ($event) => EventDTO::fromArray($event), $events);
        $result = $this->repository->bulkCreate($dtos);

        // Broadcast all events
        foreach ($events as $event) {
            $this->broadcastEvent($event);
        }

        return $result;
    }

    /**
     * Get events for a session.
     */
    public function getBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySession($sessionId);
    }

    /**
     * Get events for a session within a time range.
     */
    public function getBySessionAndTimestampRange(int $sessionId, int $from, int $to): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySessionAndTimestampRange($sessionId, $from, $to);
    }

    /**
     * Get events after a timestamp for a session.
     */
    public function getBySessionAfterTimestamp(int $sessionId, int $timestamp): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySessionAfterTimestamp($sessionId, $timestamp);
    }

    /**
     * Get canvas events for a session.
     */
    public function getCanvasEventsBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getCanvasEventsBySession($sessionId);
    }

    /**
     * Get events by user for a session.
     */
    public function getBySessionAndUser(int $sessionId, int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySessionAndUser($sessionId, $userId);
    }

    /**
     * Count events for a session.
     */
    public function countBySession(int $sessionId): int
    {
        return $this->repository->countBySession($sessionId);
    }

    /**
     * Delete all events for a session.
     */
    public function deleteBySession(int $sessionId): bool
    {
        return $this->repository->deleteBySession($sessionId);
    }

    /**
     * Archive events for a session.
     */
    public function archiveBySession(int $sessionId): bool
    {
        return $this->repository->archiveBySession($sessionId);
    }

    /**
     * Export events to JSON for recording.
     */
    public function exportToJson(int $sessionId): string
    {
        $events = $this->getBySession($sessionId);
        return json_encode($events->map(fn ($event) => [
            'type' => $event->type->value,
            'data' => $event->data,
            'timestamp_ms' => $event->timestamp_ms,
            'user_id' => $event->user_id,
        ])->toArray());
    }

    /**
     * Import events from JSON.
     */
    public function importFromJson(int $sessionId, string $json): bool
    {
        $events = json_decode($json, true);
        if (!$events) {
            return false;
        }

        $dtos = array_map(fn ($event) => new EventDTO(
            type: $event['type'],
            data: $event['data'],
            timestampMs: $event['timestamp_ms'],
            userId: $event['user_id'] ?? null,
        ), $events);

        return $this->bulkCreate(array_map(fn ($dto) => $dto->toArray(), $dtos));
    }

    /**
     * Broadcast event via WebSocket/Redis.
     */
    private function broadcastEvent(array|LiveSessionEvent $event): void
    {
        $eventData = is_array($event) ? $event : [
            'type' => $event->type->value,
            'data' => $event->data,
            'timestamp_ms' => $event->timestamp_ms,
            'user_id' => $event->user_id,
        ];

        $sessionId = is_array($event) ? $event['session_id'] : $event->session_id;

        Redis::publish("live-session:{$sessionId}", json_encode([
            'event' => 'draw_event',
            'data' => $eventData,
            'timestamp_ms' => now()->valueOf(),
        ]));
    }

    /**
     * Cache event for quick access.
     */
    private function cacheEvent(LiveSessionEvent $event): void
    {
        $cacheKey = sprintf('live-session:%d:events:buffer', $event->session_id);
        $ttl = config('live-session.cache.events_buffer_ttl', 86400);
        
        Redis::lpush($cacheKey, json_encode([
            'type' => $event->type->value,
            'data' => $event->data,
            'timestamp_ms' => $event->timestamp_ms,
            'user_id' => $event->user_id,
        ]));
        
        Redis::expire($cacheKey, $ttl);
    }
}
