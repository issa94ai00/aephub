<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->timestamps();
        });

        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->timestamps();

            $table->index(['university_id', 'id']);
        });

        Schema::create('study_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->unsignedTinyInteger('year_number'); // 1..N
            $table->string('name')->nullable(); // optional label
            $table->string('name_en')->nullable();
            $table->timestamps();

            $table->unique(['faculty_id', 'year_number']);
        });

        Schema::create('study_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_year_id')->constrained('study_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('term_number'); // 1..N
            $table->string('name')->nullable(); // optional label
            $table->string('name_en')->nullable();
            $table->timestamps();

            $table->unique(['study_year_id', 'term_number']);
            $table->index(['study_year_id', 'id']);
        });

        Schema::create('course_study_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('study_term_id')->constrained('study_terms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'study_term_id']);
            $table->index(['study_term_id', 'course_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable()->after('university')->constrained('universities')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->after('university_id')->constrained('faculties')->nullOnDelete();
            $table->foreignId('study_year_id')->nullable()->after('faculty_id')->constrained('study_years')->nullOnDelete();
            $table->foreignId('study_term_id')->nullable()->after('study_year_id')->constrained('study_terms')->nullOnDelete();
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreignId('university_id')->nullable()->after('university')->constrained('universities')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->after('university_id')->constrained('faculties')->nullOnDelete();
            $table->foreignId('study_year_id')->nullable()->after('faculty_id')->constrained('study_years')->nullOnDelete();
            $table->foreignId('study_term_id')->nullable()->after('study_year_id')->constrained('study_terms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_term_id');
            $table->dropConstrainedForeignId('study_year_id');
            $table->dropConstrainedForeignId('faculty_id');
            $table->dropConstrainedForeignId('university_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_term_id');
            $table->dropConstrainedForeignId('study_year_id');
            $table->dropConstrainedForeignId('faculty_id');
            $table->dropConstrainedForeignId('university_id');
        });

        Schema::dropIfExists('course_study_terms');
        Schema::dropIfExists('study_terms');
        Schema::dropIfExists('study_years');
        Schema::dropIfExists('faculties');
        Schema::dropIfExists('universities');
    }
};

