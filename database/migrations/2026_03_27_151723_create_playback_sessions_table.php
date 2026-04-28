<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('playback_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_video_id')->constrained('course_videos')->cascadeOnDelete();
            $table->string('device_id')->index();

            $table->string('status')->default('active')->index(); // active|revoked|expired
            $table->dateTime('issued_at');
            $table->dateTime('expires_at')->index();

            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();

            $table->string('watermark_text')->nullable();
            $table->unsignedInteger('watermark_seed')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'course_video_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playback_sessions');
    }
};
