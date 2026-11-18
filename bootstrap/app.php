<?php

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
        // Igual que en Laravel 8: limite 'api' (definido en AppServiceProvider) en el grupo api
        $middleware->throttleApi();

        // Invitados sin sesion -> login; usuarios autenticados en rutas 'guest' -> panel
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.panel'));

        // Mismos campos excluidos de TrimStrings que tenia el proyecto
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
