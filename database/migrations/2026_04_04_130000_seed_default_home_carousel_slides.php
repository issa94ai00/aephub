<?php

use Database\Seeders\HomeCarouselSlidesSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HomeCarouselSlidesSeeder::seedIfEmpty();
    }

    public function down(): void
    {
        // Intentionally empty: slides may have been edited in admin.
    }
};
