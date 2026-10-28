<?php

use App\Http\Middleware\HandlePostTooLarge;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use Intervention\Image\Exceptions\MissingDependencyException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->remove(ValidatePostSize::class);
        $middleware->web(append: [HandlePostTooLarge::class]);
        $middleware->api(append: [HandlePostTooLarge::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $message = 'Los archivos adjuntos superan el tamaño permitido para una sola solicitud. Reduce el tamaño de las fotografías (máximo 15 MB cada una) o carga menos archivos e inténtalo de nuevo.';
            $errorKey = match (true) {
                $request->routeIs('tours360.*') => 'scenes',
                $request->routeIs('properties.store', 'properties.update') => 'images',
                $request->routeIs('media.store') => 'file',
                default => 'files',
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => [$errorKey => [$message]],
                ], 413);
            }

            return back()->withErrors([$errorKey => $message]);
        });

        $exceptions->render(function (MissingDependencyException $exception, Request $request) {
            $message = 'El servidor no tiene habilitada la extensión GD necesaria para procesar fotografías. Reinicia el servidor PHP después de habilitarla.';
            $errors = ['image' => [$message]];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => $errors,
                ], 503);
            }

            return back()->withErrors($errors);
        });
    })->create();
