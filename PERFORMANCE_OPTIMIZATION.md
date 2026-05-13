# Performance Optimization Guide

## Overview

This guide provides instructions for optimizing the distance learning platform to serve a large number of users without service interruption.

## Completed Optimizations

### 1. Database Configuration
- ✅ Connection pooling enabled (config/database.php)
- ✅ Read replica support configured
- ✅ Query optimization options added

### 2. Redis Caching Layer
- ✅ LiveSessionCacheService created
- ✅ Session status caching
- ✅ Participant count caching with atomic operations
- ✅ Event buffer caching
- ✅ User active sessions caching

### 3. Rate Limiting
- ✅ LiveSessionRateLimit middleware created
- ✅ Configurable limits per route type
- ✅ Applied to all live session routes

### 4. Health Checks
- ✅ HealthCheckController created
- ✅ Basic health endpoint (/api/v1/health)
- ✅ Detailed health endpoint (/api/v1/health/detailed)
- ✅ Checks: database, Redis, storage, cache, queue

### 5. Database Query Optimization
- ✅ LiveSessionController optimized with selective eager loading
- ✅ EventController optimized with cache integration
- ✅ Column selection to reduce data transfer

### 6. Queue Worker Optimization
- ✅ OptimizeQueueWorkers command created
- ✅ Separate queue configurations for different job types
- ✅ Supervisor configuration template provided

## Environment Variables

Add these to your `.env` file:

```env
# Database Performance
DB_MAX_CONNECTIONS=100
DB_MIN_CONNECTIONS=10
DB_READ_HOST=127.0.0.1
DB_READ_PORT=3306
DB_STICKY=true

# Redis Caching
REDIS_ENABLED=true
CACHE_DEFAULT_TTL=3600
CACHE_PREFIX=aep:
CACHE_WARMING_ENABLED=true
CACHE_WARMING_SCHEDULE=*/5 * * * *

# Rate Limiting
RATE_LIMITING_ENABLED=true
WS_RATE_LIMITING_ENABLED=true
WS_MAX_CONNECTIONS_PER_USER=5
WS_MESSAGE_RATE_LIMIT=100
WS_MESSAGE_RATE_WINDOW=60

# Queue Monitoring
QUEUE_MONITORING_ENABLED=true
QUEUE_ALERT_THRESHOLD=1000

# Health Checks
HEALTH_CHECKS_ENABLED=true
METRICS_ENABLED=true
METRICS_COLLECT_INTERVAL=60

# Auto Scaling (if using cloud provider)
AUTO_SCALING_ENABLED=false
AUTO_SCALING_MIN_INSTANCES=2
AUTO_SCALING_MAX_INSTANCES=10
AUTO_SCALING_CPU_HIGH=70
AUTO_SCALING_CPU_LOW=30
AUTO_SCALING_MEMORY_HIGH=80
AUTO_SCALING_MEMORY_LOW=40
AUTO_SCALING_SCALE_UP_COOLDOWN=300
AUTO_SCALING_SCALE_DOWN_COOLDOWN=600
```

## Queue Worker Configuration

### Using Supervisor

Create `/etc/supervisor/conf.d/laravel-queue-live-session.conf`:

```ini
[program:laravel-queue-live-session]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --queue=live-session --sleep=1 --tries=5 --timeout=120 --max-jobs=500 --max-time=1800
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel/queue-live-session.log
stopwaitsecs=3600
```

Create `/etc/supervisor/conf.d/laravel-queue-recording.conf`:

```ini
[program:laravel-queue-recording]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --queue=recording --sleep=5 --tries=3 --timeout=300 --max-jobs=100 --max-time=7200
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel/queue-recording.log
stopwaitsecs=3600
```

Create `/etc/supervisor/conf.d/laravel-queue-default.conf`:

```ini
[program:laravel-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel/queue-default.log
stopwaitsec=3600
```

Update Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-live-session:*
sudo supervisorctl start laravel-queue-recording:*
sudo supervisorctl start laravel-queue-default:*
```

## Load Balancing Configuration

### Nginx Configuration

```nginx
upstream backend {
    least_conn;
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    keepalive 64;
}

server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Health Check in Load Balancer

Configure your load balancer to check `/api/v1/health` every 30 seconds.

## Auto-Scaling Configuration

### AWS Auto Scaling

Create an Auto Scaling Group with the following settings:

- **Minimum instances**: 2
- **Maximum instances**: 10
- **Desired capacity**: 2
- **Health check type**: ELB
- **Health check grace period**: 300 seconds

Scaling Policies:

**Scale Up Policy**:
- Metric: CPU Utilization > 70%
- Adjustment: +1 instance
- Cooldown: 300 seconds

**Scale Down Policy**:
- Metric: CPU Utilization < 30%
- Adjustment: -1 instance
- Cooldown: 600 seconds

### DigitalOcean App Platform

Configure scaling in your app spec:

```yaml
name: aep-platform
services:
  - name: web
    instance_count: 2
    instance_size_slug: basic-xxs
    scaling:
      min_instances: 2
      max_instances: 10
      cpu_threshold: 70
      memory_threshold: 80
```

## Monitoring

### Health Check Endpoints

- **Basic**: `GET /api/v1/health`
- **Detailed**: `GET /api/v1/health/detailed`

### Metrics Collection

Enable Prometheus metrics by installing `prometheus-laravel`:

```bash
composer require prometheus-laravel/prometheus-laravel
```

Configure in `config/prometheus.php`:

```php
<?php

return [
    'enabled' => env('METRICS_ENABLED', true),
    'collect_interval' => env('METRICS_COLLECT_INTERVAL', 60),
    'storage_adapter' => 'redis',
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DATABASE', 0),
    ],
];
```

## Performance Targets

- **Response Time**: < 200ms for API endpoints
- **Database Queries**: < 100ms average
- **Redis Operations**: < 10ms average
- **Queue Processing**: < 5 seconds for standard jobs
- **Concurrent Users**: 1000+ per live session
- **Uptime**: 99.9%

## Troubleshooting

### High Database Load

1. Check slow query log
2. Verify read replicas are being used
3. Add indexes for frequently queried columns
4. Enable query caching

### High Memory Usage

1. Check for memory leaks in queue workers
2. Reduce max_jobs and max_time in queue configuration
3. Enable opcache
4. Monitor Redis memory usage

### High CPU Usage

1. Check for inefficient queries
2. Review queue worker count
3. Enable auto-scaling
4. Profile application with XHProf

### Queue Backlog

1. Increase number of queue workers
2. Review job processing time
3. Split heavy jobs into smaller chunks
4. Use separate queues for different job types

## Scheduled Tasks

Add to `routes/console.php`:

```php
// Cache warming
$schedule->call(function () {
    if (config('performance.cache.warming.enabled')) {
        $sessions = \App\Domain\LiveSession\Models\LiveSession::where('status', 'live')->get();
        foreach ($sessions as $session) {
            app(\App\Domain\LiveSession\Services\LiveSessionCacheService::class)->warmSessionCache($session->id);
        }
    }
})->cron(config('performance.cache.warming.schedule', '*/5 * * * *'));
```

## Additional Recommendations

1. **Use Redis Cluster** for high availability
2. **Enable OPCache** for PHP performance
3. **Use CDN** for static assets
4. **Implement database connection pooling** at the application level
5. **Use read replicas** for read-heavy operations
6. **Enable HTTP/2** for better performance
7. **Use gzip compression** for responses
8. **Implement circuit breakers** for external services
9. **Use async processing** for non-critical operations
10. **Monitor and alert** on performance metrics

## Testing Performance

Use Apache Bench to test performance:

```bash
ab -n 1000 -c 100 https://your-domain.com/api/v1/live-sessions
```

Use Laravel Telescope for debugging:

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

## Summary

These optimizations will significantly improve the platform's ability to handle high concurrency without service interruption. The key improvements are:

1. **Database**: Connection pooling and read replicas
2. **Caching**: Redis for frequently accessed data
3. **Rate Limiting**: Prevent abuse and ensure fair resource allocation
4. **Health Checks**: Monitor system health
5. **Queue Optimization**: Efficient background job processing
6. **Load Balancing**: Distribute traffic across multiple instances
7. **Auto-scaling**: Automatically scale based on load

Regular monitoring and tuning will ensure optimal performance as the user base grows.
