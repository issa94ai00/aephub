<?php

namespace App\Domain\LiveSession\Policies;

use App\Domain\LiveSession\Models\LiveSessionRecording;
use App\Models\User;

class RecordingPolicy
{
    /**
     * Determine whether the user can view the recording.
     */
    public function view(User $user, LiveSessionRecording $recording): bool
    {
        $session = $recording->session;

        // Teachers can view their own session recordings
        if ($user->id === $session->teacher_id) {
            return true;
        }

        // Students enrolled in the course can view
        if ($session->course->enrollments()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Admins can view all
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the recording.
     */
    public function delete(User $user, LiveSessionRecording $recording): bool
    {
        $session = $recording->session;
        return $user->id === $session->teacher_id || $user->isAdmin();
    }
}
