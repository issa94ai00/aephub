<?php

namespace App\Domain\LiveSession\Repositories;

use App\Domain\LiveSession\DTOs\EventDTO;
use App\Domain\LiveSession\Enums\EventType;
use App\Domain\LiveSession\Models\LiveSessionEvent;
use App\Domain\LiveSession\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EventRepository implements EventRepositoryInterface
{
    public function findById(int $id): ?LiveSessionEvent
    {
        return LiveSessionEvent::find($id);
    }

    public function create(EventDTO $dto): LiveSessionEvent
    {
        return LiveSessionEvent::create($dto->toArray());
    }

    public function bulkCreate(array $events): bool
    {
        try {
            DB::beginTransaction();
            foreach ($events as $event) {
                LiveSessionEvent::create($event);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(LiveSessionEvent $event): bool
    {
        return $event->delete();
    }

    public function getBySession(int $sessionId): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function getBySessionAndTimestampRange(int $sessionId, int $from, int $to): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->betweenTimestamps($from, $to)
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function getBySessionAfterTimestamp(int $sessionId, int $timestamp): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->afterTimestamp($timestamp)
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function getBySessionAndType(int $sessionId, EventType $type): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->ofType($type)
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function getCanvasEventsBySession(int $sessionId): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->canvasEvents()
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function getBySessionAndUser(int $sessionId, int $userId): Collection
    {
        return LiveSessionEvent::where('session_id', $sessionId)
            ->byUser($userId)
            ->orderBy('timestamp_ms')
            ->get();
    }

    public function countBySession(int $sessionId): int
    {
        return LiveSessionEvent::where('session_id', $sessionId)->count();
    }

    public function deleteBySession(int $sessionId): bool
    {
        return LiveSessionEvent::where('session_id', $sessionId)->delete() > 0;
    }

    public function archiveBySession(int $sessionId): bool
    {
        // Implementation would move events to cold storage
        // This is a placeholder for the actual implementation
        return true;
    }
}
