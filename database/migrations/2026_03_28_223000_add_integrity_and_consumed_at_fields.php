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
        Schema::table('course_videos', function (Blueprint $table) {
            $table->string('encrypted_sha256', 64)->nullable()->after('key_version');
        });

        Schema::table('playback_sessions', function (Blueprint $table) {
            $table->timestamp('consumed_at')->nullable()->after('expires_at');
            $table->index('consumed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playback_sessions', function (Blueprint $table) {
            $table->dropIndex(['consumed_at']);
            $table->dropColumn('consumed_at');
        });

        Schema::table('course_videos', function (Blueprint $table) {
            $table->dropColumn('encrypted_sha256');
        });
    }
};
