<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'email',
        'code_hash',
        'expires_at',
        'failed_attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'failed_attempts' => 'integer',
        ];
    }
}
