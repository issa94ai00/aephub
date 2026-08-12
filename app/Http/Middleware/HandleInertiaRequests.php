<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use App\Support\AdminChrome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        if ($request->is('admin/login')) {
            return parent::rootView($request);
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return 'admin-app';
        }

        return parent::rootView($request);
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $localeCookie = config('locale.cookie', 'site_locale');

        return [
            ...parent::share($request),
            'site' => fn () => app(SiteSettingsService::class)->all(),
            'locale' => fn () => app()->getLocale(),
            'translations' => function () use ($request) {
                $t = [
                    'site' => Lang::get('site'),
                    'registration' => Lang::get('registration'),
                    'legal' => Lang::get('legal'),
                ];
                if ($request->routeIs('admin.*') && ! $request->routeIs('admin.login')) {
                    $t['admin'] = Lang::get('admin');
                }

                return $t;
            },
            // The admin shell — sidebar, branding, locale links — comes from
            // AdminChrome. This closure used to carry its own copy of the
            // navigation list, which is how the two drifted: entries added to
            // AdminChrome never appeared, because Inertia responses read this
            // one and AdminChrome was only reached by the SPA payload builder.
            // Storage management was the entry that went missing.
            'adminChrome' => function () use ($request) {
                if (! $request->routeIs('admin.*') || $request->routeIs('admin.login')) {
                    return null;
                }

                return AdminChrome::for($request);
            },
            'siteChrome' => function () use ($request, $localeCookie) {
                $svc = app(SiteSettingsService::class);
                $site = $svc->all();
                $isHome = $request->is('/');

                return [
                    'whatsapp_href' => $svc->resolveWhatsappHref($site),
                    'whatsapp_show' => $svc->shouldShowFloatingWhatsapp($site),
                    'is_home' => $isHome,
                    'nav_courses_href' => $isHome ? '#courses' : url('/#courses'),
                    'nav_universities_href' => $isHome ? '#universities' : url('/#universities'),
                    'nav_why_href' => $isHome ? '#why' : url('/#why'),
                    'nav_faq_href' => $isHome ? '#faq' : route('faq'),
                    'locale_ar' => route('locale.switch', ['locale' => 'ar']),
                    'locale_en' => route('locale.switch', ['locale' => 'en']),
                    'locale_active' => $request->cookie($localeCookie),
                    'routes' => [
                        'home' => url('/'),
                        'faq' => route('faq'),
                        'register' => route('subscription.register'),
                        'register_store' => route('subscription.register.store'),
                        'legal_privacy' => route('legal.privacy-terms'),
                        'android' => route('android.download'),
                        'welcome' => url('/welcome'),
                    ],
                    'api_base' => url('/api/v1'),
                ];
            },
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'old' => fn () => $request->session()->getOldInput(),
        ];
    }
}
