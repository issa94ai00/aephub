<?php

use App\Domain\LiveSession\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', EventType::values());
            $table->json('data');
            $table->unsignedBigInteger('timestamp_ms');
            $table->timestamps();

            $table->index('session_id');
            $table->index('timestamp_ms');
            $table->index('type');
            $table->index('created_at');
            $table->index(['session_id', 'timestamp_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_events');
    }
};
