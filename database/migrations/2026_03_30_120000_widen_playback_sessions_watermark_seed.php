<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `playback_sessions` MODIFY `watermark_seed` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `playback_sessions` MODIFY `watermark_seed` INT UNSIGNED NULL');
    }
};
