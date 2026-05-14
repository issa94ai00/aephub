<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminInertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Inertia\Response;

class QueueWorkerController extends Controller
{
    public function index(): Response
    {
        // Get status of workers
        $workers = ['queue-worker-1', 'queue-worker-2', 'queue-worker-3']; // Adjust as needed
        $statuses = [];

        foreach ($workers as $worker) {
            $result = Process::run("supervisorctl status {$worker}");
            $statuses[$worker] = trim($result->output());
        }

        return AdminInertia::frame('admin.queue-workers.index', compact('workers', 'statuses'));
    }

    public function manage(Request $request)
    {
        $request->validate([
            'action' => 'required|in:start,stop,restart,status',
            'workers' => 'array',
            'workers.*' => 'string',
        ]);

        $action = $request->action;
        $workers = $request->workers ?? ['queue-worker-1', 'queue-worker-2', 'queue-worker-3'];

        $exitCode = Artisan::call('supervisor:manage', [
            'action' => $action,
            '--worker' => $workers,
        ]);

        $output = Artisan::output();

        if ($exitCode === 0) {
            return redirect()->back()->with('success', "Action '{$action}' completed successfully.");
        } else {
            return redirect()->back()->with('error', "Action '{$action}' failed: " . $output);
        }
    }
}