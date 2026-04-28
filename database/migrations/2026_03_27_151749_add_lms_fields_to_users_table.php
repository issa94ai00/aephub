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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index();

            $table->string('university')->nullable();
            $table->string('study_year')->nullable();
            $table->string('study_term')->nullable();

            $table->boolean('device_lock_enabled')->default(true);
            $table->string('locked_device_id')->nullable()->index();
            $table->timestamp('locked_device_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'university',
                'study_year',
                'study_term',
                'device_lock_enabled',
                'locked_device_id',
                'locked_device_at',
            ]);
        });
    }
};
