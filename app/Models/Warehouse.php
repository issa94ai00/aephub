<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'code',
        'location',
        'phone',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function totalUnits(): int
    {
        return (int) $this->stockLevels()->sum('quantity');
    }

    public function localizedName(string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'en' && trim((string) $this->name_en) !== ''
            ? $this->name_en
            : $this->name;

        return (string) $value;
    }
}
