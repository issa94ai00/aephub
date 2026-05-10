<?php

namespace App\Services\External\LiveKit;

use Firebase\JWT\JWT;

class LiveKitTokenGenerator
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly string $algorithm = 'HS256',
    ) {}

    /**
     * Generate a token for joining a room.
     */
    public function generateToken(
        string $identity,
        string $name,
        string $roomName,
        string $role = 'guest',
        int $ttl = 3600,
        array $metadata = [],
    ): string {
        $now = time();
        $exp = $now + $ttl;
        $nbf = $now;

        $payload = [
            'iss' => $this->apiKey,
            'sub' => $identity,
            'iat' => $now,
            'exp' => $exp,
            'nbf' => $nbf,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'room' => $roomName,
                'canPublish' => $role === 'host',
                'canSubscribe' => true,
                'canPublishSources' => $role === 'host' ? ['camera', 'microphone', 'screen_share'] : [],
            ],
            'metadata' => json_encode(array_merge([
                'role' => $role,
                'name' => $name,
            ], $metadata)),
        ];

        return JWT::encode($payload, $this->apiSecret, $this->algorithm);
    }

    /**
     * Generate a token for API access.
     */
    public function generateApiToken(array $grants = [], int $ttl = 3600): string
    {
        $now = time();
        $exp = $now + $ttl;

        $payload = [
            'iss' => $this->apiKey,
            'iat' => $now,
            'exp' => $exp,
            'grants' => $grants,
        ];

        return JWT::encode($payload, $this->apiSecret, $this->algorithm);
    }

    /**
     * Validate a token.
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, $this->apiSecret, [$this->algorithm]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Decode a token without validation (for debugging).
     */
    public function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new \Firebase\JWT\Key($this->apiSecret, $this->algorithm));
        } catch (\Exception $e) {
            return null;
        }
    }
}
