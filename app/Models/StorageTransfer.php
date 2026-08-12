<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A move of everything on one destination onto another.
 *
 * The row is the progress report. A worker updates the counters as it goes, so
 * an admin who closed the tab an hour ago can reopen the storage screen and see
 * where the transfer got to — including whether it stopped, and why.
 */
class StorageTransfer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses where the worker still holds the transfer. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_RUNNING];

    protected $fillable = [
        'from_disk',
        'to_disk',
        'status',
        'total_items',
        'moved_items',
        'failed_items',
        'total_bytes',
        'moved_bytes',
        'delete_source',
        'cancel_requested',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'moved_items' => 'integer',
        'failed_items' => 'integer',
        'total_bytes' => 'integer',
        'moved_bytes' => 'integer',
        'delete_source' => 'boolean',
        'cancel_requested' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** Progress as a percentage of items, for the bar on the storage screen. */
    public function percent(): float
    {
        if ($this->total_items <= 0) {
            return $this->isActive() ? 0.0 : 100.0;
        }

        return round(min(100, (($this->moved_items + $this->failed_items) / $this->total_items) * 100), 1);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }
}
