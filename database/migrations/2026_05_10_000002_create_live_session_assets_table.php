<?php

use App\Domain\LiveSession\Enums\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('live_sessions')->onDelete('cascade');
            $table->enum('type', AssetType::values());
            $table->string('storage_disk', 50);
            $table->string('storage_path', 500);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->unsignedInteger('page_count')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('session_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_assets');
    }
};
