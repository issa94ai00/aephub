<?php

namespace App\Domain\LiveSession\Enums;

enum AssetType: string
{
    case PDF = 'pdf';
    case IMAGE = 'image';

    public function label(): string
    {
        return match ($this) {
            self::PDF => 'PDF',
            self::IMAGE => 'Image',
        };
    }

    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::PDF => ['application/pdf'],
            self::IMAGE => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        };
    }

    public static function fromMimeType(string $mimeType): ?self
    {
        foreach (self::cases() as $case) {
            if (in_array($mimeType, $case->allowedMimeTypes())) {
                return $case;
            }
        }
        return null;
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
