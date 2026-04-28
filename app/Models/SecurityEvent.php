<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'type',
        'payload',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayTypeLabel(): string
    {
        $key = 'admin.security.types.'.$this->type;
        $t = __($key);

        return $t === $key ? $this->type : $t;
    }
}
