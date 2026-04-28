<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateJwt
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return new JsonResponse(['message' => 'Unauthorized'], 401);
            }
            $request->setUserResolver(fn () => $user);
        } catch (JWTException $e) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
