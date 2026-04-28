<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiteSettingsService
{
    private const CACHE_KEY = 'site_settings.payload.v2';

    /** Relative path on the `public` disk (under storage/app/public). */
    public const MANAGED_LOGO_DIRECTORY = 'site/brand';

    /**
     * @return list<string>
     */
    public function allowedKeys(): array
    {
        return [
            'site_name',
            'site_name_en',
            'timezone',
            'locale',
            'site_logo',
            'site_background_fixed',
            'site_fixed_bg_enabled',
            'contact_phone',
            'contact_email',
            'facebook_url',
            'telegram_url',
            'whatsapp_number',
            'whatsapp_float_enabled',
            'seo_meta_title',
            'seo_meta_title_en',
            'seo_meta_description',
            'seo_meta_description_en',
            'seo_keywords',
            'seo_keywords_en',
            'seo_og_image',
            'sham_cash_code',
            'score_degree',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'site_name' => (string) config('app.name', 'LMS'),
            'site_name_en' => '',
            'timezone' => (string) config('app.timezone', 'UTC'),
            'locale' => 'ar',
            'site_logo' => '',
            'site_background_fixed' => '/images/site-bg.svg',
            'site_fixed_bg_enabled' => '1',
            'contact_phone' => '',
            'contact_email' => '',
            'facebook_url' => '',
            'telegram_url' => '',
            'whatsapp_number' => '',
            'whatsapp_float_enabled' => '1',
            'seo_meta_title' => '',
            'seo_meta_title_en' => '',
            'seo_meta_description' => 'منصة تعليمية للدورات الجامعية: محتوى منظم وتجربة مناسبة للجوال.',
            'seo_meta_description_en' => '',
            'seo_keywords' => 'تعليم,LMS,دورات جامعية',
            'seo_keywords_en' => '',
            'seo_og_image' => '',
            'sham_cash_code' => '',
            'score_degree' => '0',
        ];
    }

    /** Resolved `score_degree` for API clients (settings with fallback). */
    public function scoreDegreeValue(): string
    {
        $raw = $this->all()['score_degree'] ?? '0';
        $s = trim((string) $raw);

        return $s !== '' ? $s : '0';
    }

    /**
     * @return array<string, string>
     */
    public function rawFromDatabase(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        return SiteSetting::query()->pluck('value', 'key')->all();
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return $this->withComputed($this->defaults());
        }

        $merged = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $defaults = $this->defaults();
            $db = $this->rawFromDatabase();

            return array_merge($defaults, $db);
        });

        return $this->withComputed($merged);
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private function withComputed(array $data): array
    {
        $data['whatsapp_url'] = $this->whatsappHref($data['whatsapp_number'] ?? '');
        $loc = app()->getLocale();

        $data['site_name_resolved'] = $this->pickBilingual($data, 'site_name', 'site_name_en', $loc);
        $data['seo_meta_title_resolved'] = $this->pickBilingual($data, 'seo_meta_title', 'seo_meta_title_en', $loc);
        $data['seo_meta_description_resolved'] = $this->pickBilingual($data, 'seo_meta_description', 'seo_meta_description_en', $loc);
        $data['seo_keywords_resolved'] = $this->pickBilingual($data, 'seo_keywords', 'seo_keywords_en', $loc);

        $data['page_title'] = trim($data['seo_meta_title_resolved']) !== ''
            ? trim($data['seo_meta_title_resolved'])
            : ($data['site_name_resolved'] !== '' ? $data['site_name_resolved'] : (string) config('app.name'));

        $data['site_logo_url'] = $this->resolvePublicUrl((string) ($data['site_logo'] ?? ''));

        $fixedBgOn = $this->isTruthySetting($data['site_fixed_bg_enabled'] ?? '0');
        $bg = trim((string) ($data['site_background_fixed'] ?? ''));
        if ($bg === '') {
            $bg = '/images/site-bg.svg';
        }
        $data['site_background_fixed_resolved'] = $fixedBgOn ? $this->resolvePublicUrl($bg) : '';

        return $data;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function pickBilingual(array $data, string $primaryKey, string $englishKey, string $locale): string
    {
        if ($locale === 'en') {
            $en = trim((string) ($data[$englishKey] ?? ''));
            if ($en !== '') {
                return $en;
            }
        }

        return trim((string) ($data[$primaryKey] ?? ''));
    }

    public function resolvePublicUrl(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $v) || str_starts_with($v, '//')) {
            return $v;
        }

        return asset(ltrim($v, '/'));
    }

    public function isManagedSiteLogoPath(string $value): bool
    {
        $v = ltrim(trim($value), '/');

        return str_starts_with($v, 'storage/'.self::MANAGED_LOGO_DIRECTORY.'/');
    }

    public function deleteManagedSiteLogoFile(string $value): void
    {
        if (! $this->isManagedSiteLogoPath($value)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists(self::MANAGED_LOGO_DIRECTORY)) {
            $disk->deleteDirectory(self::MANAGED_LOGO_DIRECTORY);
        }
    }

    /**
     * Store an uploaded logo on the public disk and return the value to persist in `site_logo` (e.g. storage/site/brand/logo.png).
     */
    public function storeUploadedSiteLogo(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        if ($disk->exists(self::MANAGED_LOGO_DIRECTORY)) {
            $disk->deleteDirectory(self::MANAGED_LOGO_DIRECTORY);
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png'));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'png';
        }

        $path = $file->storeAs(self::MANAGED_LOGO_DIRECTORY, 'logo.'.$ext, 'public');

        return 'storage/'.$path;
    }

    public function whatsappHref(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return strlen($digits) >= 8 ? 'https://wa.me/'.$digits : '';
    }

    /**
     * رابط واتساب من الإعدادات (المحسوب أو المشتق من الرقم).
     *
     * @param  array<string, string>  $settings
     */
    public function resolveWhatsappHref(array $settings): string
    {
        $href = trim((string) ($settings['whatsapp_url'] ?? ''));
        if ($href !== '') {
            return $href;
        }

        return $this->whatsappHref((string) ($settings['whatsapp_number'] ?? ''));
    }

    /**
     * هل يُعرض الزر العائم: مفعّل في الإعدادات + رابط wa.me صالح.
     *
     * @param  array<string, string>  $settings
     */
    public function shouldShowFloatingWhatsapp(array $settings): bool
    {
        if (! $this->isTruthySetting($settings['whatsapp_float_enabled'] ?? '0')) {
            return false;
        }

        return $this->resolveWhatsappHref($settings) !== '';
    }

    private function isTruthySetting(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, ['1', 'true', 'on', 'yes'], true);
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function persist(array $input): void
    {
        $allowed = $this->allowedKeys();
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value]
            );
        }

        $this->flushCache();
        $this->applyToConfig();
    }

    public function applyToConfig(): void
    {
        $all = $this->all();
        config([
            'app.name' => $all['site_name'] ?? config('app.name'),
            'app.timezone' => $all['timezone'] ?? config('app.timezone'),
            'app.locale' => $all['locale'] ?? config('app.locale'),
        ]);
    }
}
