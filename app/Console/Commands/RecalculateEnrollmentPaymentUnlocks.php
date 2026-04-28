<?php

namespace App\Console\Commands;

use App\Models\CourseEnrollment;
use App\Support\EnrollmentPaymentProgress;
use Illuminate\Console\Command;

class RecalculateEnrollmentPaymentUnlocks extends Command
{
    protected $signature = 'enrollment:recalculate-payment-unlocks';

    protected $description = 'Recompute paid_amount_cents, unlocked_sessions_count, and unlocked_videos_count from approved payment_requests for all enrollments';

    public function handle(): int
    {
        $count = 0;
        CourseEnrollment::query()
            ->where('status', 'approved')
            ->orderBy('id')
            ->each(function (CourseEnrollment $e) use (&$count): void {
                EnrollmentPaymentProgress::applyToEnrollment($e);
                $count++;
            });

        $this->info("Updated {$count} enrollments.");

        return self::SUCCESS;
    }
}
