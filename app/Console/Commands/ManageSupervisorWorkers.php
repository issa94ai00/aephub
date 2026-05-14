<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class ManageSupervisorWorkers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'supervisor:manage {action : Action to perform (start|stop|restart|status)} {--worker=* : Specific worker names to manage}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Laravel queue workers via Supervisor';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $workers = $this->option('worker');

        if (!in_array($action, ['start', 'stop', 'restart', 'status'])) {
            $this->error('Invalid action. Use: start, stop, restart, or status');
            return 1;
        }

        // Default workers if none specified
        if (empty($workers)) {
            $workers = ['queue-worker-1', 'queue-worker-2', 'queue-worker-3']; // Adjust based on your supervisor config
        }

        foreach ($workers as $worker) {
            $this->info("Performing {$action} on worker: {$worker}");

            $command = "supervisorctl {$action} {$worker}";

            $result = Process::run($command);

            if ($result->successful()) {
                $this->info("Successfully {$action}ed {$worker}");
                if ($action === 'status') {
                    $this->line($result->output());
                }
            } else {
                $this->error("Failed to {$action} {$worker}: " . $result->errorOutput());
            }
        }

        return 0;
    }
}