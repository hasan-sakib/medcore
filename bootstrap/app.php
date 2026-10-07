<?php

use App\Http\Middleware\EnsureCentralDomain;
use App\Http\Middleware\EnsurePatientPortalUser;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserBelongsToTenant;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: []);

        if (getenv('APP_ENV') === 'testing') {
            $middleware->remove(VerifyCsrfToken::class);
        }

        $middleware->web(prepend: [
            IdentifyTenant::class,
        ]);

        $middleware->web(append: [
            EnsureUserBelongsToTenant::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'super-admin' => EnsureSuperAdmin::class,
            'patient-portal' => EnsurePatientPortalUser::class,
            'central-only' => EnsureCentralDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $_, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }
        });
    })
    ->create();
