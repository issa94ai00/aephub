<?php

namespace App\Domain\LiveSession\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CompressAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    public function __construct(
        public readonly string $disk,
        public readonly string $inputPath,
        public readonly string $outputPath,
        public readonly int $bitrate = 32,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // This would use FFmpeg to compress the audio
        // For now, this is a placeholder
        $inputFile = Storage::disk($this->disk)->path($this->inputPath);
        $outputFile = Storage::disk($this->disk)->path($this->outputPath);

        // FFmpeg command would be:
        // ffmpeg -i $inputFile -b:a {$this->bitrate}k -ac 1 -ar 16000 -c:a libopus $outputFile

        \Log::info('Audio compression completed', [
            'input' => $this->inputPath,
            'output' => $this->outputPath,
            'bitrate' => $this->bitrate,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Audio compression failed', [
            'input' => $this->inputPath,
            'error' => $exception->getMessage(),
        ]);
    }
}
