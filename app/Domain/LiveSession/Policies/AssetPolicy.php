<?php

namespace App\Domain\LiveSession\Policies;

use App\Domain\LiveSession\Models\LiveSessionAsset;
use App\Models\User;

class AssetPolicy
{
    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, LiveSessionAsset $asset): bool
    {
        $session = $asset->session;

        // Teachers can view their own session assets
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
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, LiveSessionAsset $asset): bool
    {
        $session = $asset->session;
        return $user->id === $session->teacher_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, LiveSessionAsset $asset): bool
    {
        $session = $asset->session;
        return $user->id === $session->teacher_id || $user->isAdmin();
    }
}
