<?php

use App\Domain\LiveSession\Enums\RecordingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->string('storage_disk', 50);
            $table->string('audio_path', 500);
            $table->string('events_path', 500);
            $table->unsignedBigInteger('duration_ms');
            $table->unsignedBigInteger('audio_size_bytes');
            $table->unsignedBigInteger('events_size_bytes');
            $table->string('codec', 20);
            $table->unsignedInteger('sample_rate');
            $table->unsignedTinyInteger('channels');
            $table->unsignedInteger('bitrate_kbps')->nullable();
            $table->enum('status', RecordingStatus::values())->default(RecordingStatus::PROCESSING->value);
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_ended_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_recordings');
    }
};
