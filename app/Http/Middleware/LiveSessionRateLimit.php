<?php

namespace App\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class LiveSessionRateLimit
{
    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, callable $next): Response
    {
        $enabled = config('performance.rate_limiting.api.enabled', true);
        
        if (!$enabled) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request);
        $limit = $this->resolveMaxAttempts($request);
        $decaySeconds = $this->resolveDecaySeconds($request);

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            return $this->buildResponse($key, $limit);
        }

        $this->limiter->hit($key, $decaySeconds);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', $this->limiter->remaining($key, $limit));
        $response->headers->set('X-RateLimit-Reset', $this->limiter->availableIn($key));

        return $response;
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? $request->ip();
        $route = $request->route()?->getName() ?? $request->path();
        
        return sha1($userId . '|' . $route);
    }

    /**
     * Resolve max attempts based on route.
     */
    protected function resolveMaxAttempts(Request $request): int
    {
        $route = $request->route()?->getName() ?? '';
        
        if (str_contains($route, 'live-session.events')) {
            return config('performance.rate_limiting.api.events.max_attempts', 300);
        }

        if (str_contains($route, 'live-session')) {
            return config('performance.rate_limiting.api.live_session.max_attempts', 100);
        }

        return config('performance.rate_limiting.api.default.max_attempts', 60);
    }

    /**
     * Resolve decay seconds.
     */
    protected function resolveDecaySeconds(Request $request): int
    {
        $route = $request->route()?->getName() ?? '';
        
        if (str_contains($route, 'live-session.events')) {
            return config('performance.rate_limiting.api.events.decay_seconds', 60);
        }

        if (str_contains($route, 'live-session')) {
            return config('performance.rate_limiting.api.live_session.decay_seconds', 60);
        }

        return config('performance.rate_limiting.api.default.decay_seconds', 60);
    }

    /**
     * Build rate limit response.
     */
    protected function buildResponse(string $key, int $limit): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ],
        ], 429)->header('Retry-After', $retryAfter);
    }
}
