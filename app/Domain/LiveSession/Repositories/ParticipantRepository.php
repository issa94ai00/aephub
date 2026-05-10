<?php

namespace App\Domain\LiveSession\Repositories;

use App\Domain\LiveSession\DTOs\ParticipantDTO;
use App\Domain\LiveSession\Enums\ParticipantRole;
use App\Domain\LiveSession\Models\LiveSessionParticipant;
use App\Domain\LiveSession\Repositories\Contracts\ParticipantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ParticipantRepository implements ParticipantRepositoryInterface
{
    public function findById(int $id): ?LiveSessionParticipant
    {
        return LiveSessionParticipant::find($id);
    }

    public function create(ParticipantDTO $dto, int $sessionId): LiveSessionParticipant
    {
        return LiveSessionParticipant::create(array_merge($dto->toArray(), [
            'session_id' => $sessionId,
            'joined_at' => now(),
        ]));
    }

    public function update(LiveSessionParticipant $participant, ParticipantDTO $dto): LiveSessionParticipant
    {
        $participant->update($dto->toArray());
        return $participant->fresh();
    }

    public function delete(LiveSessionParticipant $participant): bool
    {
        return $participant->delete();
    }

    public function getBySession(int $sessionId): Collection
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->get();
    }

    public function getActiveBySession(int $sessionId): Collection
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->active()->get();
    }

    public function getBySessionAndRole(int $sessionId, ParticipantRole $role): Collection
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->where('role', $role->value)->get();
    }

    public function getTeachersBySession(int $sessionId): Collection
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->teacher()->get();
    }

    public function getStudentsBySession(int $sessionId): Collection
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->student()->get();
    }

    public function findBySessionAndUser(int $sessionId, int $userId): ?LiveSessionParticipant
    {
        return LiveSessionParticipant::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    public function isActiveParticipant(int $sessionId, int $userId): bool
    {
        return LiveSessionParticipant::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    public function setLeft(LiveSessionParticipant $participant): bool
    {
        return $participant->update(['left_at' => now()]);
    }

    public function updateConnectionQuality(LiveSessionParticipant $participant, string $quality): bool
    {
        return $participant->update(['connection_quality' => $quality]);
    }

    public function countActiveBySession(int $sessionId): int
    {
        return LiveSessionParticipant::where('session_id', $sessionId)->active()->count();
    }

    public function getStatistics(int $sessionId): array
    {
        $participants = LiveSessionParticipant::where('session_id', $sessionId);
        
        return [
            'total' => $participants->count(),
            'active' => $participants->active()->count(),
            'by_role' => [
                'teacher' => $participants->teacher()->count(),
                'student' => $participants->student()->count(),
                'guest' => $participants->guest()->count(),
            ],
            'by_connection_quality' => [
                'excellent' => $participants->where('connection_quality', 'excellent')->count(),
                'good' => $participants->where('connection_quality', 'good')->count(),
                'fair' => $participants->where('connection_quality', 'fair')->count(),
                'poor' => $participants->where('connection_quality', 'poor')->count(),
            ],
        ];
    }
}
