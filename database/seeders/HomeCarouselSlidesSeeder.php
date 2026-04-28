<?php

namespace Database\Seeders;

use App\Models\HomeCarouselSlide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class HomeCarouselSlidesSeeder extends Seeder
{
    /**
     * Default hero slides (formerly hardcoded in HomeController).
     * Safe to run multiple times: skips if any row exists.
     */
    public static function seedIfEmpty(): void
    {
        if (! Schema::hasTable('home_carousel_slides')) {
            return;
        }

        if (HomeCarouselSlide::query()->exists()) {
            return;
        }

        $definitions = [
            [
                'sort_order' => 1,
                'title' => 'حفل تخرج — بداية مسيرة مهنية',
                'title_en' => 'Graduation — the start of your career',
                'image' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1400&q=80&auto=format&fit=crop',
            ],
            [
                'sort_order' => 2,
                'title' => 'حرم جامعي — بيئة للتعلم والانطلاق',
                'title_en' => 'Campus — a place to learn and grow',
                'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1400&q=80&auto=format&fit=crop',
            ],
            [
                'sort_order' => 3,
                'title' => 'المكتبة — مكان القراءة والتركيز',
                'title_en' => 'Library — focus and reading',
                'image' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=1400&q=80&auto=format&fit=crop',
            ],
            [
                'sort_order' => 4,
                'title' => 'طلاب جامعيون — تعاون ونجاح',
                'title_en' => 'University students — collaboration and success',
                'image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1400&q=80&auto=format&fit=crop',
            ],
        ];

        foreach ($definitions as $def) {
            HomeCarouselSlide::query()->create([
                'sort_order' => $def['sort_order'],
                'title' => $def['title'],
                'title_en' => $def['title_en'],
                'subtitle' => null,
                'subtitle_en' => null,
                'image' => $def['image'],
                'is_active' => true,
            ]);
        }
    }

    public function run(): void
    {
        self::seedIfEmpty();
    }
}
