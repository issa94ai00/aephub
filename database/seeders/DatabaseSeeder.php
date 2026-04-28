<?php

namespace Database\Seeders;

use App\Services\SiteSettingsService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * For a full reset with this structure, run:
     *   php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        if (Schema::hasTable('site_settings')) {
            app(SiteSettingsService::class)->persist([
                'site_name' => config('app.name', 'LMS'),
                'site_name_en' => 'University E-Learning Platform',
                'timezone' => config('app.timezone', 'Asia/Damascus'),
                'locale' => 'ar',
                'site_logo' => '',
                'site_background_fixed' => '/images/site-bg.svg',
                'site_fixed_bg_enabled' => true,
                'contact_phone' => '+963 9XX XXX XXX',
                'contact_email' => 'info@example.com',
                'facebook_url' => 'https://facebook.com/',
                'telegram_url' => 'https://t.me/',
                'whatsapp_number' => '963900000000',
                'whatsapp_float_enabled' => true,
                'seo_meta_title' => '',
                'seo_meta_title_en' => 'University courses — online learning',
                'seo_meta_description' => 'منصة تعليمية للدورات الجامعية: محتوى منظم، دعم للطلاب، وتجربة مناسبة للجوال.',
                'seo_meta_description_en' => 'An e-learning platform for university courses: structured content, student support, and a mobile-friendly experience.',
                'seo_keywords' => 'تعليم,LMS,دورات جامعية,تعليم عن بعد',
                'seo_keywords_en' => 'education,LMS,university courses,e-learning',
                'seo_og_image' => '',
                'score_degree' => '0',
            ]);
        }

        $this->call(DamascusUniversityStructureSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(HomeCarouselSlidesSeeder::class);
    }
}
