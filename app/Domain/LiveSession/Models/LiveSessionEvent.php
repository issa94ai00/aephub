<?php

namespace App\Domain\LiveSession\Models;

use App\Domain\LiveSession\Enums\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSessionEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'type',
        'data',
        'timestamp_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => EventType::class,
        'data' => 'array',
        'timestamp_ms' => 'integer',
    ];

    /**
     * Get the session that the event belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    /**
     * Get the user who created the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if this is a draw event.
     */
    public function isDraw(): bool
    {
        return $this->type === EventType::DRAW;
    }

    /**
     * Check if this is a page change event.
     */
    public function isPageChange(): bool
    {
        return $this->type === EventType::PAGE_CHANGE;
    }

    /**
     * Check if this is an equation event.
     */
    public function isEquation(): bool
    {
        return $this->type === EventType::EQUATION;
    }

    /**
     * Check if this is a text event.
     */
    public function isText(): bool
    {
        return $this->type === EventType::TEXT;
    }

    /**
     * Check if this is a clear event.
     */
    public function isClear(): bool
    {
        return $this->type === EventType::CLEAR;
    }

    /**
     * Check if this is an undo event.
     */
    public function isUndo(): bool
    {
        return $this->type === EventType::UNDO;
    }

    /**
     * Check if this event requires canvas rendering.
     */
    public function requiresCanvas(): bool
    {
        return $this->type->requiresCanvas();
    }

    /**
     * Check if this event requires asset context.
     */
    public function requiresAsset(): bool
    {
        return $this->type->requiresAsset();
    }

    /**
     * Check if this event requires LaTeX rendering.
     */
    public function requiresLatex(): bool
    {
        return $this->type->requiresLatex();
    }

    /**
     * Scope a query to only include events within a time range.
     */
    public function scopeBetweenTimestamps($query, int $from, int $to)
    {
        return $query->whereBetween('timestamp_ms', [$from, $to]);
    }

    /**
     * Scope a query to only include events after a timestamp.
     */
    public function scopeAfterTimestamp($query, int $timestamp)
    {
        return $query->where('timestamp_ms', '>=', $timestamp);
    }

    /**
     * Scope a query to only include events before a timestamp.
     */
    public function scopeBeforeTimestamp($query, int $timestamp)
    {
        return $query->where('timestamp_ms', '<=', $timestamp);
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeOfType($query, EventType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope a query to only include canvas events.
     */
    public function scopeCanvasEvents($query)
    {
        return $query->whereIn('type', EventType::canvasEvents());
    }

    /**
     * Scope a query to only include events created by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
