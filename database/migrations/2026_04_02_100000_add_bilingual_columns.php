<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question_en')->nullable()->after('question');
            $table->text('answer_en')->nullable()->after('answer');
        });

        Schema::table('course_videos', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('course_files', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question_en', 'answer_en']);
        });

        Schema::table('course_videos', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'description_en']);
        });

        Schema::table('course_files', function (Blueprint $table) {
            $table->dropColumn(['name_en']);
        });
    }
};
