<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_videos', function (Blueprint $table) {
            if (!Schema::hasColumn('course_videos', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('course_videos', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('course_videos', 'encrypted_sha256')) {
                $table->string('encrypted_sha256')->nullable()->after('key_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_videos', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en', 'encrypted_sha256']);
        });
    }
};
