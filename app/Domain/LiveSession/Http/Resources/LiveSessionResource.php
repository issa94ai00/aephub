<?php

namespace App\Domain\LiveSession\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'teacher' => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'email' => $this->teacher->email,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'status' => $this->status->value,
            'livekit_room_id' => $this->livekit_room_id,
            'max_participants' => $this->max_participants,
            'current_participants' => $this->current_participants,
            'settings' => $this->settings,
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'recording' => RecordingResource::make($this->whenLoaded('recordings')->first()),
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
