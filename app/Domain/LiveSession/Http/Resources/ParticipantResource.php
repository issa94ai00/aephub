<?php

namespace App\Domain\LiveSession\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'role' => $this->role->value,
            'joined_at' => $this->joined_at->format('Y-m-d H:i:s'),
            'left_at' => $this->left_at?->format('Y-m-d H:i:s'),
            'duration_ms' => $this->duration_ms,
            'human_duration' => $this->human_duration,
            'is_active' => $this->isActive(),
            'connection_quality' => $this->connection_quality,
            'ip_address' => $this->ip_address,
        ];
    }
}
