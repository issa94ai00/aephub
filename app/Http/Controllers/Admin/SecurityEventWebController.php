<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Support\AdminInertia;
use Illuminate\Http\Request;
use Inertia\Response;

class SecurityEventWebController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $q = SecurityEvent::query()
            ->with(['user:id,name,email'])
            ->latest('id');

        if (! empty($data['type'])) {
            $q->where('type', $data['type']);
        }
        if (! empty($data['user_id'])) {
            $q->where('user_id', (int) $data['user_id']);
        }
        if (! empty($data['from'])) {
            $q->where('created_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $q->where('created_at', '<=', $data['to'].' 23:59:59');
        }

        $events = $q->paginate(40)->withQueryString();

        return AdminInertia::frame('admin.security-events.index', [
            'events' => $events,
            'filters' => $data,
        ]);
    }

    public function show(SecurityEvent $securityEvent): Response
    {
        $securityEvent->load(['user:id,name,email']);

        return AdminInertia::frame('admin.security-events.show', ['securityEvent' => $securityEvent]);
    }
}
