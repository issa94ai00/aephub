<?php

namespace App\Domain\LiveSession\Models;

use App\Domain\LiveSession\Enums\AssetType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LiveSessionAsset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'type',
        'storage_disk',
        'storage_path',
        'file_name',
        'file_size',
        'mime_type',
        'page_count',
        'thumbnail_path',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => AssetType::class,
        'file_size' => 'integer',
        'page_count' => 'integer',
    ];

    /**
     * Get the session that owns the asset.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    /**
     * Get the user who uploaded the asset.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the download URL for the asset.
     */
    public function getDownloadUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->storage_disk)
            ->temporaryUrl($this->storage_path, now()->addHours(24));
    }

    /**
     * Get the thumbnail URL for the asset.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk($this->storage_disk)
            ->temporaryUrl($this->thumbnail_path, now()->addHours(24));
    }

    /**
     * Check if this is a PDF asset.
     */
    public function isPdf(): bool
    {
        return $this->type === AssetType::PDF;
    }

    /**
     * Check if this is an image asset.
     */
    public function isImage(): bool
    {
        return $this->type === AssetType::IMAGE;
    }

    /**
     * Get the file size in human-readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope a query to only include PDF assets.
     */
    public function scopePdf($query)
    {
        return $query->where('type', AssetType::PDF->value);
    }

    /**
     * Scope a query to only include image assets.
     */
    public function scopeImage($query)
    {
        return $query->where('type', AssetType::IMAGE->value);
    }
}
