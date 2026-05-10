<?php

namespace App\Services\External\LiveKit;

use Illuminate\Support\Facades\Http;

class LiveKitClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly string $host,
        private readonly int $port,
        private readonly bool $useSsl,
    ) {}

    /**
     * Get the base URL for LiveKit API.
     */
    private function getBaseUrl(): string
    {
        $scheme = $this->useSsl ? 'https' : 'http';
        return sprintf('%s://%s:%d', $scheme, $this->host, $this->port);
    }

    /**
     * Create a new room.
     */
    public function createRoom(string $roomName, array $options = []): array
    {
        $url = sprintf('%s/twirp/livekit.RoomService/CreateRoom', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, array_merge([
            'name' => $roomName,
        ], $options));

        return $response->json();
    }

    /**
     * Delete a room.
     */
    public function deleteRoom(string $roomName): bool
    {
        $url = sprintf('%s/twirp/livekit.RoomService/DeleteRoom', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, ['room' => $roomName]);

        return $response->successful();
    }

    /**
     * List rooms.
     */
    public function listRooms(array $names = []): array
    {
        $url = sprintf('%s/twirp/livekit.RoomService/ListRooms', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, ['names' => $names]);

        return $response->json();
    }

    /**
     * Get room info.
     */
    public function getRoom(string $roomName): ?array
    {
        $url = sprintf('%s/twirp/livekit.RoomService/GetRoom', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, ['room' => $roomName]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * List participants in a room.
     */
    public function listParticipants(string $roomName): array
    {
        $url = sprintf('%s/twirp/livekit.RoomService/ListParticipants', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, ['room' => $roomName]);

        return $response->json();
    }

    /**
     * Remove a participant from a room.
     */
    public function removeParticipant(string $roomName, string $participantId): bool
    {
        $url = sprintf('%s/twirp/livekit.RoomService/RemoveParticipant', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, [
            'room' => $roomName,
            'participant' => $participantId,
        ]);

        return $response->successful();
    }

    /**
     * Mute a track.
     */
    public function muteTrack(string $roomName, string $participantId, string $trackSid, bool $muted = true): bool
    {
        $url = sprintf('%s/twirp/livekit.RoomService/MutePublishedTrack', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, [
            'room' => $roomName,
            'identity' => $participantId,
            'track_sid' => $trackSid,
            'muted' => $muted,
        ]);

        return $response->successful();
    }

    /**
     * Start recording.
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        $url = sprintf('%s/twirp/livekit.EgressService/StartRoomCompositeEgress', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, array_merge([
            'room_name' => $roomName,
        ], $options));

        return $response->json();
    }

    /**
     * Stop recording.
     */
    public function stopRecording(string $egressId): bool
    {
        $url = sprintf('%s/twirp/livekit.EgressService/StopEgress', $this->getBaseUrl());

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, ['egress_id' => $egressId]);

        return $response->successful();
    }

    /**
     * List active egress (recordings).
     */
    public function listEgress(string $roomName = null): array
    {
        $url = sprintf('%s/twirp/livekit.EgressService/ListEgress', $this->getBaseUrl());

        $body = [];
        if ($roomName !== null) {
            $body['room_name'] = $roomName;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->generateAuthHeader(),
            'Content-Type' => 'application/json',
        ])->post($url, $body);

        return $response->json();
    }

    /**
     * Generate authorization header for API requests.
     */
    private function generateAuthHeader(): string
    {
        // This would use the LiveKit token generator
        // For now, return a basic auth header
        return 'Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret);
    }
}
