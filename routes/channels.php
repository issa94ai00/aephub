<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('live-session.{id}', function ($user, $id) {
    $session = \App\Domain\LiveSession\Models\LiveSession::find($id);
    if (!$session) {
        return false;
    }

    // Teacher can always join their session channel
    if ($user->id === $session->teacher_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'teacher',
        ];
    }

    // Students enrolled in the course can join
    if ($session->course->enrollments()->where('user_id', $user->id)->exists()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'student',
        ];
    }

    // Admins can join
    if ($user->isAdmin()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'admin',
        ];
    }

    return false;
});
