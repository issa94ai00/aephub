<?php

namespace App\Domain\LiveSession\Enums;

enum RecordingStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSING => 'Processing',
            self::READY => 'Ready',
            self::FAILED => 'Failed',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::READY, self::FAILED]);
    }

    public function canBePlayed(): bool
    {
        return $this === self::READY;
    }

    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
