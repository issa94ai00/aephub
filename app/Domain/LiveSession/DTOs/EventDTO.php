<?php

namespace App\Domain\LiveSession\DTOs;

use Illuminate\Http\Request;

readonly class EventDTO
{
    public function __construct(
        public string $type,
        public array $data,
        public int $timestampMs,
        public ?int $userId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            type: (string) $request->input('type'),
            data: (array) $request->input('data'),
            timestampMs: (int) $request->input('timestamp_ms'),
            userId: $request->input('user_id') ? (int) $request->input('user_id') : null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            data: $data['data'],
            timestampMs: $data['timestamp_ms'],
            userId: $data['user_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'timestamp_ms' => $this->timestampMs,
            'user_id' => $this->userId,
        ];
    }
}
