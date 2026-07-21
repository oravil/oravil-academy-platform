<?php

use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if (
                $exception->getStatusCode() === 419
                && $exception->getPrevious() instanceof TokenMismatchException
                && $request->expectsJson()
            ) {
                return response()->json([
                    'error' => [
                        'code' => 'CSRF_TOKEN_MISMATCH',
                        'message' => 'CSRF token is invalid or expired.',
                    ],
                ], 419);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'Forbidden.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (ModuleNotFoundException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Module not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                $fields = collect($exception->errors())
                    ->flatMap(fn (array $messages, string $field) => array_map(
                        fn (string $message) => ['field' => $field, 'message' => $message],
                        $messages
                    ))
                    ->values();

                return response()->json([
                    'error' => [
                        'code' => 'validation_error',
                        'message' => $fields->first()['message'] ?? 'The given data was invalid.',
                        'fields' => $fields,
                    ],
                ], 422);
            }
        });
    })->create();
