<?php

namespace App\Domain\LiveSession\DTOs;

readonly class AttendanceDTO
{
    public function __construct(
        public int $recordingId,
        public ?int $lastPositionMs,
        public ?int $durationMs,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            recordingId: (int) $request->input('recording_id'),
            lastPositionMs: $request->input('last_position_ms') ? (int) $request->input('last_position_ms') : null,
            durationMs: $request->input('duration_ms') ? (int) $request->input('duration_ms') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'recording_id' => $this->recordingId,
            'last_position_ms' => $this->lastPositionMs,
            'duration_ms' => $this->durationMs,
        ], fn ($value) => $value !== null);
    }
}
