<?php

use App\Domain\LiveSession\Enums\ParticipantRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->enum('role', ParticipantRole::values());
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->unique(['session_id', 'user_id']);
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
            $table->index('joined_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_participants');
    }
};
