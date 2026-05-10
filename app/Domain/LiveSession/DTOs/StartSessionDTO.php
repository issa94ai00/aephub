<?php

namespace App\Domain\LiveSession\DTOs;

readonly class StartSessionDTO
{
    public function __construct(
        public bool $recordingEnabled,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            recordingEnabled: (bool) $request->input('recording_enabled', true),
        );
    }

    public function toArray(): array
    {
        return [
            'recording_enabled' => $this->recordingEnabled,
        ];
    }
}
