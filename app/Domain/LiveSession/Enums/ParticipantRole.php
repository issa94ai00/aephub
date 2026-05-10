<?php

namespace App\Domain\LiveSession\Enums;

enum ParticipantRole: string
{
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case GUEST = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::TEACHER => 'Teacher',
            self::STUDENT => 'Student',
            self::GUEST => 'Guest',
        };
    }

    public function canDraw(): bool
    {
        return in_array($this, [self::TEACHER]);
    }

    public function canChat(): bool
    {
        return true;
    }

    public function canControlPlayback(): bool
    {
        return $this === self::TEACHER;
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
