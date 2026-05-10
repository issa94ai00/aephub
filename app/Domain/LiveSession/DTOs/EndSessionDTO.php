<?php

namespace App\Domain\LiveSession\DTOs;

readonly class EndSessionDTO
{
    public function __construct(
        public ?string $reason,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            reason: $request->input('reason'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reason' => $this->reason,
        ], fn ($value) => $value !== null);
    }
}
