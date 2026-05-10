<?php

namespace App\Services\External\LiveKit;

class LiveKitRoomManager
{
    public function __construct(
        private readonly LiveKitClient $client,
        private readonly LiveKitTokenGenerator $tokenGenerator,
    ) {}

    /**
     * Create a new room for a live session.
     */
    public function createRoom(string $roomName, int $maxParticipants = 1000): array
    {
        return $this->client->createRoom($roomName, [
            'empty_timeout' => config('livekit.room.default_empty_timeout', 300),
            'max_participants' => $maxParticipants,
            'enable_transcription' => false,
        ]);
    }

    /**
     * Delete a room.
     */
    public function deleteRoom(string $roomName): bool
    {
        return $this->client->deleteRoom($roomName);
    }

    /**
     * Get room information.
     */
    public function getRoom(string $roomName): ?array
    {
        return $this->client->getRoom($roomName);
    }

    /**
     * Check if a room exists.
     */
    public function roomExists(string $roomName): bool
    {
        return $this->client->getRoom($roomName) !== null;
    }

    /**
     * Generate a unique room name for a session.
     */
    public function generateRoomName(int $sessionId): string
    {
        $prefix = config('livekit.room.default_name_prefix', 'session_');
        return sprintf('%s%s', $prefix, $sessionId);
    }

    /**
     * Generate a token for a participant.
     */
    public function generateParticipantToken(
        string $roomName,
        string $identity,
        string $name,
        string $role = 'guest',
        int $ttl = 3600,
        array $metadata = [],
    ): string {
        return $this->tokenGenerator->generateToken(
            identity: $identity,
            name: $name,
            roomName: $roomName,
            role: $role,
            ttl: $ttl,
            metadata: $metadata,
        );
    }

    /**
     * Generate a token for the teacher (host).
     */
    public function generateTeacherToken(
        string $roomName,
        int $userId,
        string $name,
        int $ttl = 7200,
    ): string {
        return $this->generateParticipantToken(
            roomName: $roomName,
            identity: "user_{$userId}",
            name: $name,
            role: 'host',
            ttl: $ttl,
            metadata: ['user_id' => $userId],
        );
    }

    /**
     * Generate a token for a student.
     */
    public function generateStudentToken(
        string $roomName,
        int $userId,
        string $name,
        int $ttl = 3600,
    ): string {
        return $this->generateParticipantToken(
            roomName: $roomName,
            identity: "user_{$userId}",
            name: $name,
            role: 'guest',
            ttl: $ttl,
            metadata: ['user_id' => $userId],
        );
    }

    /**
     * Get participants in a room.
     */
    public function getParticipants(string $roomName): array
    {
        return $this->client->listParticipants($roomName);
    }

    /**
     * Remove a participant from a room.
     */
    public function removeParticipant(string $roomName, string $participantId): bool
    {
        return $this->client->removeParticipant($roomName, $participantId);
    }

    /**
     * Start recording for a room.
     */
    public function startRecording(
        string $roomName,
        string $outputPath,
        string $outputFormat = 'mp4',
    ): array {
        return $this->client->startRecording($roomName, [
            'output' => [
                'file' => [
                    'file_type' => $outputFormat,
                    'filepath' => $outputPath,
                ],
            ],
            'audio_only' => true,
        ]);
    }

    /**
     * Stop recording.
     */
    public function stopRecording(string $egressId): bool
    {
        return $this->client->stopRecording($egressId);
    }

    /**
     * Get active recordings for a room.
     */
    public function getActiveRecordings(string $roomName = null): array
    {
        return $this->client->listEgress($roomName);
    }
}
