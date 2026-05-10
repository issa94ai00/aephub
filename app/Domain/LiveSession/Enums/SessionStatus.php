<?php

namespace App\Domain\LiveSession\Enums;

enum SessionStatus: string
{
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::LIVE => 'Live',
            self::ENDED => 'Ended',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED]);
    }

    public function canStart(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function canEnd(): bool
    {
        return $this === self::LIVE;
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
