<?php

use App\Http\Middleware\AccountFreezeMiddleware;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\AuthenticateMultipartLocalPartOrJwt;
use App\Http\Middleware\DeviceLockMiddleware;
use App\Http\Middleware\EnsureAdminWeb;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\SetSiteLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ValidateSignature;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->web(append: [
            SetSiteLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->api(append: [
            SetApiLocale::class,
        ]);

        $middleware->alias([
            'auth.jwt' => AuthenticateJwt::class,
            'auth.multipart_local_part' => AuthenticateMultipartLocalPartOrJwt::class,
            'account.freeze' => AccountFreezeMiddleware::class,
            'device.lock' => DeviceLockMiddleware::class,
            'role' => RoleMiddleware::class,
            'admin.web' => EnsureAdminWeb::class,
            'signed' => ValidateSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
