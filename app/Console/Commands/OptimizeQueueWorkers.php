<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class OptimizeQueueWorkers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:optimize {--queue=default : The queue to optimize}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize queue workers for high concurrency';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queue = $this->option('queue');
        $config = config("performance.queue.workers.{$queue}", config('performance.queue.workers.default'));

        $this->info("Optimizing queue workers for: {$queue}");
        $this->info("Configuration:");
        $this->table(['Setting', 'Value'], [
            ['Connection', $config['connection']],
            ['Queue', $config['queue']],
            ['Sleep', $config['sleep']],
            ['Tries', $config['tries']],
            ['Timeout', $config['timeout']],
            ['Max Jobs', $config['max_jobs']],
            ['Max Time', $config['max_time']],
        ]);

        // Start optimized workers
        $this->info("Starting optimized queue workers...");

        $command = sprintf(
            'php artisan queue:work %s --queue=%s --sleep=%d --tries=%d --timeout=%d --max-jobs=%d --max-time=%d',
            $config['connection'],
            $config['queue'],
            $config['sleep'],
            $config['tries'],
            $config['timeout'],
            $config['max_jobs'],
            $config['max_time']
        );

        $this->info("Command: {$command}");
        
        // For production, you would use Supervisor or process managers
        $this->warn("For production, use Supervisor or systemd to manage workers");
        $this->warn("Example Supervisor configuration:");
        
        $supervisorConfig = <<<EOT
[program:laravel-queue-{$queue}]
process_name=%(program_name)s_%(process_num)02d
command=php {$command}
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel/queue-{$queue}.log
stopwaitsecs=3600
EOT;

        $this->info($supervisorConfig);

        return Command::SUCCESS;
    }
}
