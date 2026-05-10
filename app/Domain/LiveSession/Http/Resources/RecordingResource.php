<?php

namespace App\Domain\LiveSession\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audio_url' => $this->when($this->canBePlayed(), $this->audio_url),
            'events_url' => $this->when($this->canBePlayed(), $this->events_url),
            'duration_ms' => $this->duration_ms,
            'human_duration' => $this->human_duration,
            'audio_size_bytes' => $this->audio_size_bytes,
            'events_size_bytes' => $this->events_size_bytes,
            'total_size_bytes' => $this->total_size_bytes,
            'human_total_size' => $this->human_total_size,
            'codec' => $this->codec,
            'sample_rate' => $this->sample_rate,
            'channels' => $this->channels,
            'bitrate_kbps' => $this->bitrate_kbps,
            'status' => $this->status->value,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
