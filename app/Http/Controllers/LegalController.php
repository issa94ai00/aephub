<?php

namespace App\Http\Controllers;

use App\Services\SiteSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function privacyTerms(SiteSettingsService $settings): Response
    {
        $site = $settings->all();
        $siteName = trim((string) ($site['site_name_resolved'] ?? '')) !== ''
            ? trim((string) $site['site_name_resolved'])
            : (string) config('app.name');

        return Inertia::render('Site/Legal/PrivacyTerms', [
            'seo' => [
                'title' => __('legal.document_title').' — '.$siteName,
                'description' => __('legal.meta_description'),
            ],
        ]);
    }
}
