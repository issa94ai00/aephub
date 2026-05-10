<?php

use App\Domain\LiveSession\Enums\SessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', SessionStatus::values())->default(SessionStatus::SCHEDULED->value);
            $table->string('livekit_room_id', 100)->unique()->nullable();
            $table->unsignedInteger('max_participants')->default(1000);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('course_id');
            $table->index('teacher_id');
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('livekit_room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
