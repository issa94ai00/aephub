<?php

namespace App\Support;

use Illuminate\Contracts\View\View;

class AdminFrame
{
    /**
     * Render an admin Blade view (extends admin.spa-inner) wrapped in the full
     * admin Blade layout (admin.layout).
     *
     * @param  array<string, mixed>  $data
     */
    public static function frame(string $blade, array $data = []): View
    {
        $sections = view($blade, $data)->renderSections();

        $title = self::plainSection($sections, 'title', __('admin.layout.default_title'));
        $heading = self::plainSection($sections, 'heading', $title);
        $subheading = self::plainSection($sections, 'subheading', '');

        return view('admin.layout', [
            'title' => $title,
            'heading' => $heading,
            'subheading' => $subheading !== '' ? $subheading : null,
            'content' => $sections['content'] ?? '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private static function plainSection(array $sections, string $key, string $default): string
    {
        if (! isset($sections[$key])) {
            return $default;
        }

        $raw = $sections[$key];
        if (! is_string($raw)) {
            return $default;
        }

        $t = trim(strip_tags($raw));

        return $t !== '' ? $t : $default;
    }
}
