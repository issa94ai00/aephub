<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Basic health check endpoint.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Detailed health check endpoint.
     */
    public function detailed(): JsonResponse
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'storage' => $this->checkStorage(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
            ],
        ];

        // Determine overall status
        $overallStatus = 'ok';
        foreach ($health['checks'] as $check) {
            if ($check['status'] === 'error') {
                $overallStatus = 'error';
                break;
            } elseif ($check['status'] === 'warning' && $overallStatus !== 'error') {
                $overallStatus = 'warning';
            }
        }

        $health['status'] = $overallStatus;

        return response()->json($health, $overallStatus === 'ok' ? 200 : 503);
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            if ($latency > 100) {
                return [
                    'status' => 'warning',
                    'message' => 'Database latency is high',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connection.
     */
    protected function checkRedis(): array
    {
        try {
            $startTime = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            if ($latency > 50) {
                return [
                    'status' => 'warning',
                    'message' => 'Redis latency is high',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage availability.
     */
    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('performance.cache.redis.enabled') ? 's3' : 'local');
            
            // Test write and read
            $testFile = 'health_check_' . time() . '.txt';
            $disk->put($testFile, 'test');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            if ($content !== 'test') {
                return [
                    'status' => 'error',
                    'message' => 'Storage read/write test failed',
                ];
            }

            return [
                'status' => 'ok',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            Cache::put('health_check_test', 'ok', 60);
            $value = Cache::get('health_check_test');
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            if ($value !== 'ok') {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write test failed',
                ];
            }

            if ($latency > 50) {
                return [
                    'status' => 'warning',
                    'message' => 'Cache latency is high',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status.
     */
    protected function checkQueue(): array
    {
        try {
            $queueSize = Redis::connection('default')->command('LLEN', ['queues:default']);
            
            $threshold = config('performance.queue.monitoring.alert_threshold', 1000);
            
            if ($queueSize > $threshold) {
                return [
                    'status' => 'warning',
                    'message' => 'Queue size is above threshold',
                    'queue_size' => $queueSize,
                    'threshold' => $threshold,
                ];
            }

            return [
                'status' => 'ok',
                'queue_size' => $queueSize,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
