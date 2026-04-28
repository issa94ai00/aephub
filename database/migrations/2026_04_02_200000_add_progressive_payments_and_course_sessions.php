<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('amount_paid_cents')->nullable()->after('status');
            $table->unsignedTinyInteger('progress_percent')->nullable()->after('amount_paid_cents');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_amount_cents')->default(0)->after('approved_by');
            $table->unsignedInteger('unlocked_videos_count')->default(0)->after('paid_amount_cents');
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('course_session_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('course_video_id')->constrained('course_videos')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['course_session_id', 'course_video_id']);
            $table->index(['course_session_id', 'sort_order']);
        });

        Schema::create('course_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['course_session_id', 'user_id']);
            $table->index(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_session_attendances');
        Schema::dropIfExists('course_session_videos');
        Schema::dropIfExists('course_sessions');

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['paid_amount_cents', 'unlocked_videos_count']);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn(['amount_paid_cents', 'progress_percent']);
        });
    }
};

