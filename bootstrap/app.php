<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CSRF exceptions
        $middleware->validateCsrfTokens(except: [
            '*/chat',
            
        ]);
         $middleware->alias([
            'admin.auth' => AdminAuth::class,
             'admin' => AdminMiddleware::class,
             'company.config' => \App\Http\Middleware\ApplyCompanyConfig::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
