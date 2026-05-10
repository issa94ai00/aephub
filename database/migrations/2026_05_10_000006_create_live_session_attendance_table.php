<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recording_id')->constrained('live_session_recordings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('duration_ms');
            $table->decimal('completion_pct', 5, 2)->default(0.00);
            $table->unsignedBigInteger('last_position_ms');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('recording_id');
            $table->index('user_id');
            $table->index('started_at');
            $table->unique(['recording_id', 'user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_attendance');
    }
};
