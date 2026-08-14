<?php

namespace App\Support;

use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdminChrome
{
    /**
     * Build the admin shell chrome (nav, branding, logout, locale URLs) for the
     * admin SPA. Returns null when the current request does not belong to an
     * authenticated admin context.
     *
     * @return array<string, mixed>|null
     */
    public static function for(Request $request): ?array
    {
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
            'nav' => self::nav(),
        ];
    }

    /**
     * The SPA sidebar.
     *
     * Entries are declared by route *name* and resolved through
     * {@see self::link()}, which drops any whose route is not registered. That
     * guard is not defensive programming for its own sake: this list already
     * names five warehouse screens whose routes do not exist, and because the
     * old code called `route()` on them directly, building the sidebar threw a
     * RouteNotFoundException — so the SPA shell rendered no navigation at all,
     * for any page. A half-built section should cost its own entry, not the
     * whole menu.
     *
     * The Blade shell has its own copy of this list in
     * `admin/partials/sidebar.blade.php`. The two are separate because the two
     * shells are, and an entry added here has to be added there as well.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function nav(): array
    {
        return array_values(array_filter([
            self::link('admin.dashboard', ['admin.dashboard'], __('admin.nav.dashboard'), 'grid', null, 'overview'),
            self::link('admin.statistics', ['admin.statistics'], __('admin.nav.statistics'), 'chart', null, 'overview'),
            self::link('admin.payments.index', ['admin.payments.*'], __('admin.nav.registration_payments'), 'card', null, 'students'),
            self::link('admin.courses.student-catalog', ['admin.courses.student-catalog'], __('admin.nav.student_courses'), 'book-open', null, 'students'),
            self::link('admin.device-change-requests.index', ['admin.device-change-requests.*'], __('admin.nav.device_change_requests'), 'device', null, 'students'),
            self::link('admin.users.index', ['admin.users.*'], __('admin.nav.users'), 'users', null, 'students'),
            self::link(
                'admin.courses.index',
                ['admin.courses.index', 'admin.courses.create', 'admin.courses.edit', 'admin.courses.sessions.*'],
                __('admin.nav.course_management'),
                'book',
                null,
                'content'
            ),
            self::link(
                'admin.exams.index',
                ['admin.exams.index', 'admin.exams.create', 'admin.exams.edit', 'admin.exams.attempts.*'],
                __('admin.nav.exams'),
                'exam',
                null,
                'content'
            ),
            self::link('admin.exams.reports', ['admin.exams.reports'], __('admin.nav.exam_reports'), 'report', null, 'content'),
            self::link('admin.academics.universities.index', ['admin.academics.*'], __('admin.nav.academics'), 'academic', null, 'content'),

            // Warehouse screens. The controllers and Vue pages exist but no
            // routes are registered for them, so these resolve to nothing and
            // are dropped until someone wires them up.
            self::link('admin.stock.index', ['admin.stock.index', 'admin.stock.movements'], __('admin.nav.stock'), 'stock', __('admin.nav.warehouse_system'), 'warehouse'),
            self::link('admin.stock.balances', ['admin.stock.balances'], __('admin.nav.stock_balances'), 'table', __('admin.nav.warehouse_system'), 'warehouse'),
            self::link('admin.stock.organize', ['admin.stock.organize'], __('admin.nav.stock_organize'), 'organize', __('admin.nav.warehouse_system'), 'warehouse'),
            self::link('admin.products.index', ['admin.products.*'], __('admin.nav.products'), 'box', __('admin.nav.warehouse_system'), 'warehouse'),
            self::link('admin.warehouses.index', ['admin.warehouses.*'], __('admin.nav.warehouses'), 'warehouse', __('admin.nav.warehouse_system'), 'warehouse'),

            self::link('admin.teachers.index', ['admin.teachers.*'], __('admin.nav.teachers'), 'teacher', null, 'content'),
            self::link('admin.security-events.index', ['admin.security-events.*'], __('admin.nav.security_logs'), 'shield', null, 'system'),
            self::link('admin.carousel.index', ['admin.carousel.*'], __('admin.nav.carousel'), 'carousel', null, 'system'),
            self::link('admin.faqs.index', ['admin.faqs.*'], __('admin.nav.faqs'), 'faq', null, 'system'),
            self::link('admin.queue-workers.index', ['admin.queue-workers.*'], __('admin.nav.queue_workers'), 'cog', null, 'system'),
            self::link('admin.settings.index', ['admin.settings.*'], __('admin.nav.settings'), 'cog', null, 'system'),

            // Storage management. Sits beside settings, as it does in the Blade
            // sidebar, because it is the same kind of thing: platform
            // configuration rather than day-to-day content.
            self::link('admin.storage-settings.index', ['admin.storage-settings.*'], __('admin.nav.storage'), 'cloud', null, 'system'),

            self::link('admin.user-reports.index', ['admin.user-reports.*'], __('admin.nav.user_reports'), 'report', null, 'system'),
        ]));
    }

    /**
     * One sidebar entry, or null when its route is not registered.
     *
     * @param  list<string>  $match  route-name patterns that light this entry up
     * @return array<string, mixed>|null
     */
    private static function link(string $routeName, array $match, string $label, string $icon, ?string $group = null, ?string $section = null): ?array
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return array_filter([
            'href' => route($routeName),
            'match' => $match,
            'label' => $label,
            'icon' => $icon,
            'group' => $group,
            'section' => $section,
        ], fn ($value) => $value !== null);
    }
}
