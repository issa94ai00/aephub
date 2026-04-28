<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingsAdminController extends Controller
{
    public function __construct(
        private SiteSettingsService $siteSettings
    ) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sham_cash_code' => ['present', 'nullable', 'string', 'max:255'],
        ]);

        $raw = $data['sham_cash_code'];
        $code = null;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            $code = $trimmed !== '' ? $trimmed : null;
        }

        $this->siteSettings->persist([
            'sham_cash_code' => $code,
        ]);

        $fresh = $this->siteSettings->all();
        $freshRaw = $fresh['sham_cash_code'] ?? null;
        $freshCode = null;
        if (is_string($freshRaw)) {
            $t = trim($freshRaw);
            $freshCode = $t !== '' ? $t : null;
        }

        return response()->json([
            'message' => 'updated',
            'site_settings' => [
                'sham_cash_code' => $freshCode,
            ],
        ]);
    }
}

