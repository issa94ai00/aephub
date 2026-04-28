<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Local multipart part uploads: clients often PUT raw bytes to the signed URL without a Bearer
 * token (same as S3). A valid encrypted part_token proves identity until it expires.
 * Falls back to JWT when part_token is absent (e.g. tests or custom clients).
 */
class AuthenticateMultipartLocalPartOrJwt
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $partToken = (string) $request->query('part_token', '');
        if ($partToken !== '') {
            $user = $this->userFromPartToken($partToken);
            if ($user !== null) {
                $request->setUserResolver(fn () => $user);

                return $next($request);
            }

            return new JsonResponse(['message' => 'Invalid or expired part token'], 422);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (! $user) {
                return new JsonResponse(['message' => 'Unauthorized'], 401);
            }
            $request->setUserResolver(fn () => $user);
        } catch (JWTException) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function userFromPartToken(string $token): ?User
    {
        try {
            $json = Crypt::decryptString($token);
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                return null;
            }
            if ((int) ($decoded['exp'] ?? 0) < now()->timestamp) {
                return null;
            }
            $uid = (int) ($decoded['uid'] ?? 0);
            if ($uid < 1) {
                return null;
            }

            return User::query()->find($uid);
        } catch (Throwable) {
            return null;
        }
    }
}
