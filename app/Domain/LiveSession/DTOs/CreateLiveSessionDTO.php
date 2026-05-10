<?php

namespace App\Domain\LiveSession\DTOs;

use Illuminate\Http\Request;

readonly class CreateLiveSessionDTO
{
    public function __construct(
        public int $courseId,
        public ?int $lessonId,
        public string $title,
        public ?string $description,
        public ?\DateTimeInterface $scheduledAt,
        public int $maxParticipants,
        public array $settings,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            courseId: (int) $request->input('course_id'),
            lessonId: $request->input('lesson_id') ? (int) $request->input('lesson_id') : null,
            title: (string) $request->input('title'),
            description: $request->input('description'),
            scheduledAt: $request->input('scheduled_at') ? new \DateTime($request->input('scheduled_at')) : null,
            maxParticipants: (int) $request->input('max_participants', 1000),
            settings: (array) $request->input('settings', []),
        );
    }

    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'lesson_id' => $this->lessonId,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'max_participants' => $this->maxParticipants,
            'settings' => $this->settings,
        ];
    }
}
