<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseVideo;
use App\Services\SiteSettingsService;
use App\Support\AdminInertia;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private SiteSettingsService $siteSettings
    ) {}

    public function index(): Response
    {
        $settings = $this->siteSettings->all();
        $app = [
            'env' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'url' => config('app.url'),
        ];

        $timezones = \DateTimeZone::listIdentifiers();

        return AdminInertia::frame('admin.settings.index', compact('settings', 'app', 'timezones'));
    }

    public function update(Request $request): RedirectResponse
    {
        $allowedLocales = ['ar', 'en'];
        $tzList = \DateTimeZone::listIdentifiers();

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_name_en' => ['nullable', 'string', 'max:120'],
            'timezone' => ['required', 'string', Rule::in($tzList)],
            'locale' => ['required', 'string', Rule::in($allowedLocales)],
            'site_logo' => ['nullable', 'string', 'max:2048'],
            'site_logo_upload' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,webp,gif'],
            'site_background_fixed' => ['nullable', 'string', 'max:2048'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'telegram_url' => ['nullable', 'string', 'max:500'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'seo_meta_title' => ['nullable', 'string', 'max:200'],
            'seo_meta_title_en' => ['nullable', 'string', 'max:200'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
            'seo_meta_description_en' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'seo_keywords_en' => ['nullable', 'string', 'max:500'],
            'seo_og_image' => ['nullable', 'string', 'max:2048'],
            'score_degree' => ['required', 'string', 'max:64'],
        ]);

        $currentLogo = trim((string) ($this->siteSettings->all()['site_logo'] ?? ''));
        $removeLogo = $request->boolean('remove_site_logo');

        if ($removeLogo) {
            $this->siteSettings->deleteManagedSiteLogoFile($currentLogo);
            $data['site_logo'] = '';
        } elseif ($request->hasFile('site_logo_upload')) {
            $this->siteSettings->deleteManagedSiteLogoFile($currentLogo);
            $data['site_logo'] = $this->siteSettings->storeUploadedSiteLogo($request->file('site_logo_upload'));
        } else {
            $newLogo = trim((string) ($data['site_logo'] ?? ''));
            if ($currentLogo !== '' && $this->siteSettings->isManagedSiteLogoPath($currentLogo)) {
                if ($newLogo === '' || $newLogo !== $currentLogo) {
                    $this->siteSettings->deleteManagedSiteLogoFile($currentLogo);
                }
            }
            $data['site_logo'] = $newLogo;
        }

        $data['whatsapp_float_enabled'] = $request->boolean('whatsapp_float_enabled');
        $data['site_fixed_bg_enabled'] = $request->boolean('site_fixed_bg_enabled');

        $this->siteSettings->persist($data);

        return redirect()->route('admin.settings.index')->with('status', __('admin.flash.settings_saved'));
    }

    public function rotateEncryptionKey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'old_app_key' => ['required', 'string', 'max:255'],
            'new_app_key' => ['required', 'string', 'max:255'],
            'confirm_rotate' => ['accepted'],
        ]);

        $oldAppKey = trim($this->normalizeAppKey($data['old_app_key']));
        $newAppKey = trim($this->normalizeAppKey($data['new_app_key']));
        $currentAppKey = trim($this->normalizeAppKey((string) config('app.key')));
        $cipher = (string) config('app.cipher');

        try {
            $oldEncrypter = new Encrypter($oldAppKey, $cipher);
            $newEncrypter = new Encrypter($newAppKey, $cipher);
            $currentEncrypter = new Encrypter($currentAppKey, $cipher);
        } catch (\Throwable $exception) {
            return redirect()->route('admin.settings.index')->withErrors([
                'old_app_key' => __('admin.settings.invalid_app_key'),
                'new_app_key' => __('admin.settings.invalid_app_key'),
            ]);
        }

        if ($newAppKey !== $currentAppKey) {
            config(['app.key' => $newAppKey]);
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach (CourseVideo::query()->whereNotNull('encrypted_content_key')->cursor() as $video) {
            $encrypted = (string) $video->encrypted_content_key;
            if ($encrypted === '') {
                $skipped++;
                continue;
            }

            $decrypted = null;
            $usedKey = null;
            foreach (['new' => $newEncrypter, 'current' => $currentEncrypter, 'old' => $oldEncrypter] as $name => $encrypter) {
                try {
                    $decrypted = $encrypter->decryptString($encrypted);
                    $usedKey = $name;
                    break;
                } catch (DecryptException) {
                    continue;
                }
            }

            if ($decrypted === null) {
                $failed++;
                continue;
            }

            if ($usedKey === 'new') {
                $skipped++;
                continue;
            }

            try {
                $reEncrypted = $newEncrypter->encryptString($decrypted);
                $roundtrip = $newEncrypter->decryptString($reEncrypted);
            } catch (DecryptException) {
                $failed++;
                continue;
            }

            if (! hash_equals($roundtrip, $decrypted)) {
                $failed++;
                continue;
            }

            $video->encrypted_content_key = $reEncrypted;
            $video->save();
            $processed++;
        }

        return redirect()->route('admin.settings.index')
            ->with('status', __('admin.flash.encryption_rotate_success', [
                'processed' => $processed,
                'skipped' => $skipped,
                'failed' => $failed,
            ]));
    }

    private function normalizeAppKey(string $key): string
    {
        $key = trim($key);

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            return $decoded === false ? $key : $decoded;
        }

        return $key;
    }

    public function clearCache(Request $request): RedirectResponse
    {
        $request->validate([
            'clear_cache' => ['accepted'],
        ]);

        $this->siteSettings->flushCache();
        Artisan::call('optimize:clear');

        return redirect()->route('admin.settings.index')->with('status', __('admin.flash.cache_cleared'));
    }
}
