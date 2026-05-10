<?php

namespace App\Domain\LiveSession\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recording_id' => $this->recording_id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'started_at' => $this->started_at->format('Y-m-d H:i:s'),
            'ended_at' => $this->ended_at?->format('Y-m-d H:i:s'),
            'duration_ms' => $this->duration_ms,
            'human_duration' => $this->human_duration,
            'completion_pct' => (float) $this->completion_pct,
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_partial' => $this->isPartial(),
            'last_position_ms' => $this->last_position_ms,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
