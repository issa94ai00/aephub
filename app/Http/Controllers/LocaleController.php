<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supported = config('locale.supported', ['ar', 'en']);

        $cookieName = (string) config('locale.cookie', 'site_locale');

        if ($locale === 'auto') {
            return redirect()
                ->back(fallback: url('/'))
                ->withoutCookie($cookieName);
        }

        if (! in_array($locale, $supported, true)) {
            abort(404);
        }

        $minutes = (int) config('locale.cookie_minutes', 60 * 24 * 365);
        $secure = (bool) config('session.secure');

        return redirect()
            ->back(fallback: url('/'))
            ->withCookie(cookie(
                $cookieName,
                $locale,
                $minutes,
                '/',
                null,
                $secure,
                true,
                false,
                'lax'
            ));
    }
}
