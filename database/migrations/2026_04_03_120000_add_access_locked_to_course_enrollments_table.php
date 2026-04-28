<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->boolean('access_locked')->default(false)->after('approved_by');
            $table->timestamp('access_locked_at')->nullable()->after('access_locked');
            $table->foreignId('access_locked_by')->nullable()->after('access_locked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropForeign(['access_locked_by']);
            $table->dropColumn(['access_locked', 'access_locked_at', 'access_locked_by']);
        });
    }
};
