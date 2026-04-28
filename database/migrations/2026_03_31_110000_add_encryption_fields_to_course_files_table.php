<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_files', function (Blueprint $table) {
            $table->string('cipher')->default('AES-128-CBC');
            $table->text('content_key'); // base64 16 bytes
            $table->string('content_iv'); // base64 16 bytes
            $table->string('key_version')->default('v1');
            $table->string('encrypted_sha256')->nullable();

            $table->index(['course_id', 'key_version']);
        });
    }

    public function down(): void
    {
        Schema::table('course_files', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'key_version']);
            $table->dropColumn([
                'cipher',
                'content_key',
                'content_iv',
                'key_version',
                'encrypted_sha256',
            ]);
        });
    }
};

