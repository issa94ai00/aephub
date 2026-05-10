<?php

namespace App\Domain\LiveSession\DTOs;

use Illuminate\Http\Request;

readonly class ParticipantDTO
{
    public function __construct(
        public int $userId,
        public string $role,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            role: (string) $request->input('role'),
            ipAddress: $request->input('ip_address'),
            userAgent: $request->input('user_agent'),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role' => $this->role,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
