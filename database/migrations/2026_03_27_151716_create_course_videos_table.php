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
        Schema::create('course_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            // Storage
            $table->string('storage_disk')->default('s3'); // s3|local|cdn
            $table->string('storage_path'); // e.g. s3 key
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('mime_type')->nullable();

            // Encryption metadata (video encrypted client-side on teacher device)
            $table->string('encryption_cipher')->default('AES-128-CBC');
            $table->text('encrypted_content_key'); // Laravel-encrypted per-video AES key
            $table->string('content_iv'); // base64 IV used for AES-128-CBC file encryption
            $table->string('key_version')->default('v1');

            $table->string('status')->default('active')->index(); // active|disabled
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_videos');
    }
};
