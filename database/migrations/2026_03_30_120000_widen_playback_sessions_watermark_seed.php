<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widens `watermark_seed` so a seed can use the full 64-bit range.
 *
 * The statement was raw MySQL (`ALTER TABLE ... MODIFY`), which SQLite has no
 * syntax for — so every test run, which builds the schema on an in-memory SQLite
 * database, died here and took the whole suite with it. Guarded by driver now:
 * MySQL keeps the original DDL, and anything else is left alone because the
 * column is created wide enough by the table's own migration on those drivers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->isMySql()) {
            return;
        }

        DB::statement('ALTER TABLE `playback_sessions` MODIFY `watermark_seed` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! $this->isMySql()) {
            return;
        }

        DB::statement('ALTER TABLE `playback_sessions` MODIFY `watermark_seed` INT UNSIGNED NULL');
    }

    private function isMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
