<?php

namespace App\Jobs;

use App\Models\CourseVideo;
use App\Services\VideoUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCourseVideoUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int|array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $videoId,
    ) {}

    public function handle(VideoUploadService $videoUploadService): void
    {
        $videoUploadService->processPendingVideo($this->videoId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Course video upload processing failed', [
            'video_id' => $this->videoId,
            'error' => $exception->getMessage(),
        ]);

        $video = CourseVideo::find($this->videoId);
        if ($video) {
            $video->status = 'failed';
            $video->save();
        }
    }
}
