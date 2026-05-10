<?php

namespace App\Domain\LiveSession\Policies;

use App\Domain\LiveSession\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    /**
     * Determine whether the user can view any live sessions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the live session.
     */
    public function view(User $user, LiveSession $session): bool
    {
        // Teachers can view their own sessions
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
     * Determine whether the user can create live sessions.
     */
    public function create(User $user): bool
    {
        // Only teachers can create sessions
        return $user->isTeacher() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the live session.
     */
    public function update(User $user, LiveSession $session): bool
    {
        // Only the session teacher or admin can update
        return $user->id === $session->teacher_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the live session.
     */
    public function delete(User $user, LiveSession $session): bool
    {
        // Only the session teacher or admin can delete
        return $user->id === $session->teacher_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can start the live session.
     */
    public function start(User $user, LiveSession $session): bool
    {
        // Only the session teacher can start
        return $user->id === $session->teacher_id;
    }

    /**
     * Determine whether the user can end the live session.
     */
    public function end(User $user, LiveSession $session): bool
    {
        // Only the session teacher can end
        return $user->id === $session->teacher_id;
    }

    /**
     * Determine whether the user can cancel the live session.
     */
    public function cancel(User $user, LiveSession $session): bool
    {
        // Only the session teacher can cancel
        return $user->id === $session->teacher_id;
    }
}
