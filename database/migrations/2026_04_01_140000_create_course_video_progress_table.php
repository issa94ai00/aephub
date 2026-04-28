<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_video_id')->constrained('course_videos')->cascadeOnDelete();
            $table->unsignedBigInteger('position_ms')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'course_video_id']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_video_progress');
    }
};
