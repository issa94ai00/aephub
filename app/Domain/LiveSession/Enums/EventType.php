<?php

namespace App\Domain\LiveSession\Enums;

enum EventType: string
{
    case DRAW = 'draw';
    case PAGE_CHANGE = 'page_change';
    case EQUATION = 'equation';
    case TEXT = 'text';
    case CLEAR = 'clear';
    case UNDO = 'undo';

    public function label(): string
    {
        return match ($this) {
            self::DRAW => 'Draw',
            self::PAGE_CHANGE => 'Page Change',
            self::EQUATION => 'Equation',
            self::TEXT => 'Text',
            self::CLEAR => 'Clear',
            self::UNDO => 'Undo',
        };
    }

    public function requiresCanvas(): bool
    {
        return in_array($this, [self::DRAW, self::CLEAR, self::UNDO]);
    }

    public function requiresAsset(): bool
    {
        return in_array($this, [self::PAGE_CHANGE]);
    }

    public function requiresLatex(): bool
    {
        return $this === self::EQUATION;
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function canvasEvents(): array
    {
        return [self::DRAW, self::CLEAR, self::UNDO];
    }
}
