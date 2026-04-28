<?php

namespace App\Support;

use Inertia\Inertia;
use Inertia\Response;

class AdminInertia
{
    /**
     * Render an admin Blade view (extends admin.spa-inner) inside the Inertia shell.
     *
     * @param  array<string, mixed>  $data
     */
    public static function frame(string $blade, array $data = []): Response
    {
        $sections = view($blade, $data)->renderSections();

        $title = self::plainSection($sections, 'title', __('admin.layout.default_title'));
        $heading = self::plainSection($sections, 'heading', $title);
        $subheading = self::plainSection($sections, 'subheading', '');

        return Inertia::render('Admin/Frame', [
            'html' => $sections['content'] ?? '',
            'title' => $title,
            'heading' => $heading,
            'subheading' => $subheading !== '' ? $subheading : null,
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
