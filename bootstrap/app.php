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

        // Rutas de las apps: identidad y propiedad tomadas del token de Sanctum
        $middleware->alias([
            'propio' => \App\Http\Middleware\ApiPropietario::class,
        ]);

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
        // La API siempre responde JSON (las apps no mandan Accept: application/json).
        // Sin token valido: 401 en JSON, nunca una redireccion al login del panel.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => 401, 'mensaje' => 'Sesión no válida, inicie sesión de nuevo'], 401);
            }
        });

        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
