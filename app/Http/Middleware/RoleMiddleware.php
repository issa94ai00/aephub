<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $allowed = array_map('strtolower', $roles);
        $userRole = strtolower((string) ($user->role ?? 'student'));
        if (!in_array($userRole, $allowed, true)) {
            return new JsonResponse(['message' => 'Forbidden'], 403);
        }

        if (
            $userRole === 'teacher'
            && ($user->teacher_approval_status ?? null) !== 'approved'
        ) {
            return new JsonResponse([
                'message' => 'Teacher account pending approval',
                'approval_status' => $user->teacher_approval_status,
            ], 403);
        }

        return $next($request);
    }
}
