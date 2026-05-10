<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\Models\LiveSession;
use App\Domain\LiveSession\Models\LiveSessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify participants that a session is starting.
     */
    public function notifySessionStarting(LiveSession $session): void
    {
        $participants = $session->participants()->student()->get();
        
        foreach ($participants as $participant) {
            $this->sendPushNotification(
                user: $participant->user,
                title: __('live_session.notification.session_starting.title'),
                body: __('live_session.notification.session_starting.body', ['title' => $session->title]),
                data: [
                    'type' => 'live_session_starting',
                    'session_id' => $session->id,
                ],
            );
        }

        Log::info('Session starting notification sent', ['session_id' => $session->id]);
    }

    /**
     * Notify participants that a session has ended.
     */
    public function notifySessionEnded(LiveSession $session): void
    {
        $participants = $session->participants()->student()->get();
        
        foreach ($participants as $participant) {
            $this->sendPushNotification(
                user: $participant->user,
                title: __('live_session.notification.session_ended.title'),
                body: __('live_session.notification.session_ended.body', ['title' => $session->title]),
                data: [
                    'type' => 'live_session_ended',
                    'session_id' => $session->id,
                    'recording_available' => $session->recordings()->ready()->exists(),
                ],
            );
        }

        Log::info('Session ended notification sent', ['session_id' => $session->id]);
    }

    /**
     * Notify that recording is ready.
     */
    public function notifyRecordingReady(int $sessionId): void
    {
        $session = LiveSession::find($sessionId);
        if (!$session) {
            return;
        }

        $participants = $session->participants()->student()->get();
        
        foreach ($participants as $participant) {
            $this->sendPushNotification(
                user: $participant->user,
                title: __('live_session.notification.recording_ready.title'),
                body: __('live_session.notification.recording_ready.body', ['title' => $session->title]),
                data: [
                    'type' => 'recording_ready',
                    'session_id' => $session->id,
                ],
            );
        }

        Log::info('Recording ready notification sent', ['session_id' => $session->id]);
    }

    /**
     * Notify a user when they join a session.
     */
    public function notifyUserJoined(LiveSessionParticipant $participant): void
    {
        $session = $participant->session;
        
        // Notify teacher
        $this->sendPushNotification(
            user: $session->teacher,
            title: __('live_session.notification.user_joined.title'),
            body: __('live_session.notification.user_joined.body', ['name' => $participant->user->name]),
            data: [
                'type' => 'user_joined',
                'session_id' => $session->id,
                'user_id' => $participant->user_id,
            ],
        );
    }

    /**
     * Notify a user when they leave a session.
     */
    public function notifyUserLeft(LiveSessionParticipant $participant): void
    {
        $session = $participant->session;
        
        // Notify teacher
        $this->sendPushNotification(
            user: $session->teacher,
            title: __('live_session.notification.user_left.title'),
            body: __('live_session.notification.user_left.body', ['name' => $participant->user->name]),
            data: [
                'type' => 'user_left',
                'session_id' => $session->id,
                'user_id' => $participant->user_id,
            ],
        );
    }

    /**
     * Send email notification.
     */
    public function sendEmailNotification(User $user, string $subject, string $htmlBody): void
    {
        \Illuminate\Support\Facades\Mail::to($user)->send(
            new \App\Mail\LiveSessionNotification($subject, $htmlBody)
        );
    }

    /**
     * Send push notification.
     */
    protected function sendPushNotification(User $user, string $title, string $body, array $data = []): void
    {
        // This would integrate with FCM or other push notification service
        // For now, just log it
        Log::info('Push notification', [
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Send reminder for upcoming session.
     */
    public function sendSessionReminder(LiveSession $session): void
    {
        $participants = $session->course->enrollments()->where('status', 'active')->get();
        
        foreach ($participants as $enrollment) {
            $this->sendPushNotification(
                user: $enrollment->user,
                title: __('live_session.notification.session_reminder.title'),
                body: __('live_session.notification.session_reminder.body', [
                    'title' => $session->title,
                    'time' => $session->scheduled_at->format('H:i'),
                    'date' => $session->scheduled_at->format('Y-m-d'),
                ]),
                data: [
                    'type' => 'session_reminder',
                    'session_id' => $session->id,
                ],
            );
        }

        Log::info('Session reminder sent', ['session_id' => $session->id]);
    }
}
