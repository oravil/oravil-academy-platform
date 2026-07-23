<?php

use App\Application\Assignment\Exceptions\AssignmentAlreadySubmittedException;
use App\Application\Assignment\Exceptions\AssignmentNotFoundException;
use App\Application\Assignment\Exceptions\AssignmentNotUnlockedException;
use App\Application\LearningPath\Exceptions\LessonLockedException;
use App\Application\LearningPath\Exceptions\LessonNotFoundException;
use App\Application\LearningPath\Exceptions\ModuleNotFoundException;
use App\Application\Progress\Exceptions\ModuleNotCompleteException;
use App\Application\Survey\Exceptions\SurveyAlreadySubmittedException;
use App\Application\Survey\Exceptions\SurveyNotFoundException;
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

        // Guests on /v1/* API routes receive the 401 JSON error contract instead
        // of a redirect; the redirect only applies to non-API requests.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('v1/*') ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /v1/* request is an API request by contract and must receive the
        // JSON error contract, even when the client sends no Accept: application/json header.
        $isApiRequest = fn (Request $request): bool => $request->is('v1/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApiRequest);

        $exceptions->render(function (HttpException $exception, Request $request) use ($isApiRequest) {
            if (
                $exception->getStatusCode() === 419
                && $exception->getPrevious() instanceof TokenMismatchException
                && $isApiRequest($request)
            ) {
                return response()->json([
                    'error' => [
                        'code' => 'CSRF_TOKEN_MISMATCH',
                        'message' => 'CSRF token is invalid or expired.',
                    ],
                ], 419);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'Forbidden.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (ModuleNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Module not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (LessonNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Lesson not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (LessonLockedException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'This lesson is locked.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (AssignmentNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Assignment not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (AssignmentNotUnlockedException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'This assignment is not yet unlocked.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (AssignmentAlreadySubmittedException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'This assignment has already been submitted.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (ModuleNotCompleteException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'This module is not yet complete.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (SurveyNotFoundException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Survey not found.',
                    ],
                ], 404);
            }
        });

        $exceptions->render(function (SurveyAlreadySubmittedException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'This survey has already been submitted.',
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
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
