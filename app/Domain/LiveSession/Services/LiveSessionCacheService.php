<?php

namespace App\Domain\LiveSession\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class LiveSessionCacheService
{
    private readonly string $prefix;

    public function __construct()
    {
        $this->prefix = config('performance.cache.redis.prefix', 'aep:');
    }

    /**
     * Cache live session status.
     */
    public function cacheSessionStatus(int $sessionId, string $status, int $ttl = 300): void
    {
        $key = $this->getSessionStatusKey($sessionId);
        Cache::put($key, $status, $ttl);
    }

    /**
     * Get cached live session status.
     */
    public function getSessionStatus(int $sessionId): ?string
    {
        $key = $this->getSessionStatusKey($sessionId);
        return Cache::get($key);
    }

    /**
     * Cache participant count for a session.
     */
    public function cacheParticipantCount(int $sessionId, int $count, int $ttl = 60): void
    {
        $key = $this->getParticipantCountKey($sessionId);
        Cache::put($key, $count, $ttl);
    }

    /**
     * Get cached participant count.
     */
    public function getParticipantCount(int $sessionId): ?int
    {
        $key = $this->getParticipantCountKey($sessionId);
        return Cache::get($key);
    }

    /**
     * Increment participant count atomically.
     */
    public function incrementParticipantCount(int $sessionId): int
    {
        $key = $this->getParticipantCountKey($sessionId);
        return Redis::incr($key);
    }

    /**
     * Decrement participant count atomically.
     */
    public function decrementParticipantCount(int $sessionId): int
    {
        $key = $this->getParticipantCountKey($sessionId);
        return Redis::decr($key);
    }

    /**
     * Cache event buffer for a session.
     */
    public function cacheEventBuffer(int $sessionId, array $events, int $ttl = 60): void
    {
        $key = $this->getEventBufferKey($sessionId);
        Cache::put($key, $events, $ttl);
    }

    /**
     * Get cached event buffer.
     */
    public function getEventBuffer(int $sessionId): array
    {
        $key = $this->getEventBufferKey($sessionId);
        return Cache::get($key, []);
    }

    /**
     * Append event to buffer atomically.
     */
    public function appendEventToBuffer(int $sessionId, array $event): void
    {
        $key = $this->getEventBufferKey($sessionId);
        Redis::rpush($key, json_encode($event));
        
        // Set TTL if not set
        if (Redis::ttl($key) === -1) {
            Redis::expire($key, 60);
        }
    }

    /**
     * Get and clear event buffer.
     */
    public function getAndClearEventBuffer(int $sessionId): array
    {
        $key = $this->getEventBufferKey($sessionId);
        $events = [];
        
        while ($event = Redis::lpop($key)) {
            $events[] = json_decode($event, true);
        }
        
        return $events;
    }

    /**
     * Cache user's active sessions.
     */
    public function cacheUserActiveSessions(int $userId, array $sessionIds, int $ttl = 300): void
    {
        $key = $this->getUserActiveSessionsKey($userId);
        Cache::put($key, $sessionIds, $ttl);
    }

    /**
     * Get user's active sessions.
     */
    public function getUserActiveSessions(int $userId): array
    {
        $key = $this->getUserActiveSessionsKey($userId);
        return Cache::get($key, []);
    }

    /**
     * Add session to user's active sessions.
     */
    public function addUserActiveSession(int $userId, int $sessionId): void
    {
        $key = $this->getUserActiveSessionsKey($userId);
        Redis::sadd($key, $sessionId);
        Redis::expire($key, 300);
    }

    /**
     * Remove session from user's active sessions.
     */
    public function removeUserActiveSession(int $userId, int $sessionId): void
    {
        $key = $this->getUserActiveSessionsKey($userId);
        Redis::srem($key, $sessionId);
    }

    /**
     * Clear all session-related cache.
     */
    public function clearSessionCache(int $sessionId): void
    {
        $keys = [
            $this->getSessionStatusKey($sessionId),
            $this->getParticipantCountKey($sessionId),
            $this->getEventBufferKey($sessionId),
        ];
        
        Cache::forgetMultiple($keys);
    }

    /**
     * Warm cache for a live session.
     */
    public function warmSessionCache(int $sessionId): void
    {
        $session = \App\Domain\LiveSession\Models\LiveSession::find($sessionId);
        
        if (!$session) {
            return;
        }

        // Cache session status
        $this->cacheSessionStatus($sessionId, $session->status->value);

        // Cache participant count
        $participantCount = $session->participants()->where('left_at', null)->count();
        $this->cacheParticipantCount($sessionId, $participantCount);
    }

    /**
     * Get session status key.
     */
    private function getSessionStatusKey(int $sessionId): string
    {
        return $this->prefix . "live_session:{$sessionId}:status";
    }

    /**
     * Get participant count key.
     */
    private function getParticipantCountKey(int $sessionId): string
    {
        return $this->prefix . "live_session:{$sessionId}:participants";
    }

    /**
     * Get event buffer key.
     */
    private function getEventBufferKey(int $sessionId): string
    {
        return $this->prefix . "live_session:{$sessionId}:events_buffer";
    }

    /**
     * Get user active sessions key.
     */
    private function getUserActiveSessionsKey(int $userId): string
    {
        return $this->prefix . "user:{$userId}:active_sessions";
    }
}
