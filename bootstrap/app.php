<?php

use App\Http\Middleware\ApiIpAllowlist;
use App\Http\Middleware\AuthApiClient;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\StudentSessionTimeout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.api_client' => AuthApiClient::class,
            'api.ip_allowlist' => ApiIpAllowlist::class,
            'role' => EnsureUserRole::class,
        ]);

        $middleware->web(append: [MaintenanceMode::class, StudentSessionTimeout::class]);

        // Trust all proxies so HTTPS/session cookies work behind nginx/Apache
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->validateCsrfTokens(except: [
            'lti/login',
            'lti/launch',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
