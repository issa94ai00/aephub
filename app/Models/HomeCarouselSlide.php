<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualStrings;
use App\Services\SiteSettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HomeCarouselSlide extends Model
{
    use HasBilingualStrings;

    protected $fillable = [
        'sort_order',
        'title',
        'title_en',
        'subtitle',
        'subtitle_en',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (HomeCarouselSlide $slide): void {
            $slide->deleteStoredImageFiles();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function resolvedImageUrl(): string
    {
        return app(SiteSettingsService::class)->resolvePublicUrl((string) ($this->image ?? ''));
    }

    public function localizedTitle(): string
    {
        return $this->bilingualString('title', 'title_en');
    }

    public function localizedSubtitle(): string
    {
        return $this->bilingualString('subtitle', 'subtitle_en');
    }

    public function usesManagedStorage(): bool
    {
        $img = ltrim(trim((string) ($this->image ?? '')), '/');

        return (bool) preg_match('#^storage/site/carousel/\d+\.[a-z0-9]{2,8}$#i', $img);
    }

    public function deleteStoredImageFiles(): void
    {
        $disk = Storage::disk('public');
        $dir = 'site/carousel';
        if (! $disk->exists($dir)) {
            return;
        }
        foreach ($disk->files($dir) as $file) {
            if (str_starts_with(basename($file), (string) $this->id.'.')) {
                $disk->delete($file);
            }
        }
    }
}
