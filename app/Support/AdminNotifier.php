<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotification;

class AdminNotifier
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function notify(string $type, string $title, ?string $body = null, ?array $data = null): void
    {
        $adminIds = User::query()
            ->where('role', 'admin')
            ->pluck('id')
            ->all();

        foreach ($adminIds as $adminId) {
            UserNotification::create([
                'user_id' => $adminId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }
}

