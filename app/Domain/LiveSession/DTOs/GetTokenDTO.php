<?php

namespace App\Domain\LiveSession\DTOs;

readonly class GetTokenDTO
{
    public function __construct(
        public string $role,
        public array $metadata,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            role: (string) $request->input('role'),
            metadata: (array) $request->input('metadata', []),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'metadata' => $this->metadata,
        ];
    }
}
