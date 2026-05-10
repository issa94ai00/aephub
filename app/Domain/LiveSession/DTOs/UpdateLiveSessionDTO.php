<?php

namespace App\Domain\LiveSession\DTOs;

use Illuminate\Http\Request;

readonly class UpdateLiveSessionDTO
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?\DateTimeInterface $scheduledAt,
        public ?int $maxParticipants,
        public ?array $settings,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            scheduledAt: $request->input('scheduled_at') ? new \DateTime($request->input('scheduled_at')) : null,
            maxParticipants: $request->input('max_participants') ? (int) $request->input('max_participants') : null,
            settings: $request->input('settings') ? (array) $request->input('settings') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'max_participants' => $this->maxParticipants,
            'settings' => $this->settings,
        ], fn ($value) => $value !== null);
    }
}
