<?php

namespace App\Domain\LiveSession\DTOs;

readonly class RecordingDTO
{
    public function __construct(
        public string $storageDisk,
        public string $audioPath,
        public string $eventsPath,
        public int $durationMs,
        public int $audioSizeBytes,
        public int $eventsSizeBytes,
        public string $codec,
        public int $sampleRate,
        public int $channels,
        public ?int $bitrateKbps,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            storageDisk: $data['storage_disk'],
            audioPath: $data['audio_path'],
            eventsPath: $data['events_path'],
            durationMs: $data['duration_ms'],
            audioSizeBytes: $data['audio_size_bytes'],
            eventsSizeBytes: $data['events_size_bytes'],
            codec: $data['codec'],
            sampleRate: $data['sample_rate'],
            channels: $data['channels'],
            bitrateKbps: $data['bitrate_kbps'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'storage_disk' => $this->storageDisk,
            'audio_path' => $this->audioPath,
            'events_path' => $this->eventsPath,
            'duration_ms' => $this->durationMs,
            'audio_size_bytes' => $this->audioSizeBytes,
            'events_size_bytes' => $this->eventsSizeBytes,
            'codec' => $this->codec,
            'sample_rate' => $this->sampleRate,
            'channels' => $this->channels,
            'bitrate_kbps' => $this->bitrateKbps,
        ];
    }
}
