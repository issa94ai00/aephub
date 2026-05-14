<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminInertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(25)
            ->get();

        $failedJobsCount = DB::table('failed_jobs')->count();

        return AdminInertia::frame('admin.queue-workers.index', compact(
            'workers',
            'statuses',
            'failedJobs',
            'failedJobsCount'
        ));
    }

    public function manage(Request $request)
    {
        $request->validate([
            'action' => 'required|in:start,stop,restart,status,retry-job,forget-job',
            'workers' => 'array',
            'workers.*' => 'string',
            'job_ids' => 'array',
            'job_ids.*' => 'integer',
        ]);

        $action = $request->action;
        $workers = $request->workers ?? ['queue-worker-1', 'queue-worker-2', 'queue-worker-3'];
        $jobIds = $request->job_ids ?? [];

        if (in_array($action, ['start', 'stop', 'restart', 'status'], true)) {
            $exitCode = Artisan::call('supervisor:manage', [
                'action' => $action,
                '--worker' => $workers,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                return redirect()->back()->with('success', "Action '{$action}' completed successfully.");
            }

            return redirect()->back()->with('error', "Action '{$action}' failed: " . $output);
        }

        if (in_array($action, ['retry-job', 'forget-job'], true)) {
            if (empty($jobIds)) {
                return redirect()->back()->with('error', 'No failed job selected.');
            }

            foreach ($jobIds as $jobId) {
                if ($action === 'retry-job') {
                    Artisan::call('queue:retry', ['id' => $jobId]);
                } else {
                    Artisan::call('queue:forget', ['id' => $jobId]);
                }
            }

            $label = $action === 'retry-job' ? 'Retried' : 'Forgot';
            return redirect()->back()->with('success', "{$label} selected failed job(s) successfully.");
        }

        return redirect()->back()->with('error', "Action '{$action}' is not supported.");
    }
}