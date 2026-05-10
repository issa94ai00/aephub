<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
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
            'adminChrome' => function () use ($request) {
                if (! $request->routeIs('admin.*') || $request->routeIs('admin.login')) {
                    return null;
                }
                $user = $request->user();
                if ($user === null) {
                    return null;
                }

                $svc = app(SiteSettingsService::class);
                $site = $svc->all();
                $sideLogo = trim((string) ($site['site_logo_url'] ?? ''));
                $headerTitle = trim((string) ($site['site_name_resolved'] ?? '')) !== ''
                    ? $site['site_name_resolved']
                    : ($site['site_name'] ?? config('app.name'));

                $nav = [
                    [
                        'href' => route('admin.dashboard'),
                        'match' => ['admin.dashboard'],
                        'label' => __('admin.nav.dashboard'),
                        'icon' => 'grid',
                    ],
                    [
                        'href' => route('admin.statistics'),
                        'match' => ['admin.statistics'],
                        'label' => __('admin.nav.statistics'),
                        'icon' => 'chart',
                    ],
                    [
                        'href' => route('admin.payments.index'),
                        'match' => ['admin.payments.*'],
                        'label' => __('admin.nav.registration_payments'),
                        'icon' => 'card',
                    ],
                    [
                        'href' => route('admin.courses.student-catalog'),
                        'match' => ['admin.courses.student-catalog'],
                        'label' => __('admin.nav.student_courses'),
                        'icon' => 'book-open',
                    ],
                    [
                        'href' => route('admin.device-change-requests.index'),
                        'match' => ['admin.device-change-requests.*'],
                        'label' => __('admin.nav.device_change_requests'),
                        'icon' => 'device',
                    ],
                    [
                        'href' => route('admin.users.index'),
                        'match' => ['admin.users.*'],
                        'label' => __('admin.nav.users'),
                        'icon' => 'users',
                    ],
                    [
                        'href' => route('admin.courses.index'),
                        'match' => ['admin.courses.index', 'admin.courses.create', 'admin.courses.edit', 'admin.courses.sessions.*'],
                        'label' => __('admin.nav.course_management'),
                        'icon' => 'book',
                    ],
                    [
                        'href' => route('admin.academics.universities.index'),
                        'match' => ['admin.academics.*'],
                        'label' => __('admin.nav.academics'),
                        'icon' => 'academic',
                    ],
                    [
                        'href' => route('admin.teachers.index'),
                        'match' => ['admin.teachers.*'],
                        'label' => __('admin.nav.teachers'),
                        'icon' => 'teacher',
                    ],
                    [
                        'href' => route('admin.security-events.index'),
                        'match' => ['admin.security-events.*'],
                        'label' => __('admin.nav.security_logs'),
                        'icon' => 'shield',
                    ],
                    [
                        'href' => route('admin.carousel.index'),
                        'match' => ['admin.carousel.*'],
                        'label' => __('admin.nav.carousel'),
                        'icon' => 'carousel',
                    ],
                    [
                        'href' => route('admin.faqs.index'),
                        'match' => ['admin.faqs.*'],
                        'label' => __('admin.nav.faqs'),
                        'icon' => 'faq',
                    ],
                    [
                        'href' => route('admin.settings.index'),
                        'match' => ['admin.settings.*'],
                        'label' => __('admin.nav.settings'),
                        'icon' => 'cog',
                    ],
                ];

                return [
                    'routeName' => $request->route()?->getName(),
                    'locale' => app()->getLocale(),
                    'userName' => $user->name,
                    'appName' => config('app.name'),
                    'siteLogoUrl' => $sideLogo,
                    'headerLogoUrl' => $sideLogo,
                    'headerTitle' => $headerTitle,
                    'logoutAction' => route('admin.logout'),
                    'localeUrls' => [
                        'ar' => route('locale.switch', ['locale' => 'ar']),
                        'en' => route('locale.switch', ['locale' => 'en']),
                        'auto' => route('locale.switch', ['locale' => 'auto']),
                    ],
                    'routes' => [
                        'dashboard' => route('admin.dashboard'),
                        'home' => url('/'),
                    ],
                    'nav' => $nav,
                ];
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
