<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class AdminSpa
{
    /**
     * Build the JSON payload that the admin Vue Router SPA expects for a page.
     * Mirrors the shape the Inertia pages previously consumed (props, url, name)
     * so the front-end state stays familiar.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function payload(Request $request, string $name, array $props): array
    {
        return [
            'name' => $name,
            'url' => $request->getRequestUri(),
            'props' => [
                ...$props,
                'locale' => app()->getLocale(),
                'translations' => [
                    'admin' => Lang::get('admin'),
                ],
                'adminChrome' => AdminChrome::for($request),
                'flash' => [
                    'status' => $request->session()->get('status'),
                ],
                'errors' => self::errors($request),
            ],
        ];
    }

    /**
     * Return either the JSON payload (for the SPA's axios fetches) or the Blade
     * shell view (for the initial browser load). Both share the same URL.
     *
     * @param  array<string, mixed>  $props
     */
    public static function respond(Request $request, string $name, array $props): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
    {
        $payload = self::payload($request, $name, $props);

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return view('admin-app', ['boot' => $payload]);
    }

    /**
     * Serialize the session error bag (if any) into a JSON-friendly array.
     *
     * @return array<string, mixed>
     */
    private static function errors(Request $request): array
    {
        $errors = $request->session()->get('errors');

        if ($errors instanceof ViewErrorBag) {
            return $errors->toArray();
        }

        if ($errors instanceof MessageBag) {
            return $errors->toArray();
        }

        if (is_array($errors)) {
            return $errors;
        }

        return [];
    }
}
