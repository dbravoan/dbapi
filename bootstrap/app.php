<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RequireModule;
use App\Http\Middleware\RequireTenant;
use App\Http\Middleware\ValidateApiVersion;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'identify_tenant' => IdentifyTenant::class,
            'tenant'          => RequireTenant::class,
            'api.version'     => ValidateApiVersion::class,
            'require.module'  => RequireModule::class,
        ]);

        if (class_exists(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class)) {
            $middleware->statefulApi();
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
