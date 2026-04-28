<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AccountFreezeMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        if (($user->status ?? 'active') === 'frozen') {
            return new JsonResponse([
                'message' => 'Account is frozen',
                'status' => 'frozen',
            ], 423);
        }

        return $next($request);
    }
}

