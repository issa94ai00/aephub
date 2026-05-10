<?php

namespace App\Domain\LiveSession\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public readonly User $user,
        public readonly string $subject,
        public readonly string $htmlBody,
        public readonly ?Mailable $mailable = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->mailable) {
                Mail::to($this->user)->send($this->mailable);
            } else {
                Mail::html($this->htmlBody, $this->subject)->to($this->user->email);
            }

            Log::info('Notification sent successfully', [
                'user_id' => $this->user->id,
                'subject' => $this->subject,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job failed', [
            'user_id' => $this->user->id,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
