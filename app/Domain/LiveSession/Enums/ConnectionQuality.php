<?php

namespace App\Domain\LiveSession\Enums;

enum ConnectionQuality: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Excellent',
            self::GOOD => 'Good',
            self::FAIR => 'Fair',
            self::POOR => 'Poor',
        };
    }

    public function score(): int
    {
        return match ($this) {
            self::EXCELLENT => 4,
            self::GOOD => 3,
            self::FAIR => 2,
            self::POOR => 1,
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 4 => self::EXCELLENT,
            $score >= 3 => self::GOOD,
            $score >= 2 => self::FAIR,
            default => self::POOR,
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
