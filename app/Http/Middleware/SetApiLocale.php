<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('locale.supported', ['ar', 'en']);
        $fromHeader = strtolower(trim((string) $request->header('X-Locale', '')));

        if (in_array($fromHeader, $supported, true)) {
            App::setLocale($fromHeader);
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
