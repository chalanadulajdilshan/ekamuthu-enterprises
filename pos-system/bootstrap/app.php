<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'branch' => \App\Http\Middleware\BranchContext::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
        // Named routes, not '/login': the app runs in a sub-folder (/ekamuthu-enterprises/pos-system).
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('pos'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
