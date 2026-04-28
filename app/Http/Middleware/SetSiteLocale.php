<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetSiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('locale.supported', ['ar', 'en']);
        $cookieName = (string) config('locale.cookie', 'site_locale');
        $fromCookie = $request->cookie($cookieName);

        if (is_string($fromCookie) && in_array($fromCookie, $supported, true)) {
            App::setLocale($fromCookie);
        } else {
            $preferred = $request->getPreferredLanguage($supported);
            if (! is_string($preferred) || ! in_array($preferred, $supported, true)) {
                $preferred = config('app.locale');
            }
            if (! in_array($preferred, $supported, true)) {
                $preferred = 'ar';
            }
            App::setLocale($preferred);
        }

        try {
            Carbon::setLocale(App::getLocale());
        } catch (\Throwable) {
            //
        }

        return $next($request);
    }
}
