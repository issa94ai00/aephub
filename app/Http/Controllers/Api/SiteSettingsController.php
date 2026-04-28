<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    public function __construct(
        private SiteSettingsService $siteSettings
    ) {}

    public function show(): JsonResponse
    {
        $all = $this->siteSettings->all();
        $raw = $all['sham_cash_code'] ?? null;

        $code = null;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            $code = $trimmed !== '' ? $trimmed : null;
        }

        return response()
            ->json([
                'site_settings' => [
                    'sham_cash_code' => $code,
                    'score_degree' => $this->siteSettings->scoreDegreeValue(),
                ],
            ])
            ->header('Cache-Control', 'public, max-age=60');
    }
}

