<?php

namespace App\Domain\LiveSession\Policies;

use App\Domain\LiveSession\Models\LiveSessionEvent;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view the event.
     */
    public function view(User $user, LiveSessionEvent $event): bool
    {
        $session = $event->session;

        // Teachers can view their own session events
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
     * Determine whether the user can create events.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, LiveSessionEvent $event): bool
    {
        $session = $event->session;
        return $user->id === $session->teacher_id || $user->isAdmin();
    }
}
