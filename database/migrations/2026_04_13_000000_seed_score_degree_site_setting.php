<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $exists = DB::table('site_settings')->where('key', 'score_degree')->exists();
        if ($exists) {
            return;
        }

        DB::table('site_settings')->insert([
            'key' => 'score_degree',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where('key', 'score_degree')->delete();
    }
};
