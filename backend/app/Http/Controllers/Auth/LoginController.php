<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\LearnerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt($request->validated())) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid credentials.',
                ],
            ], 401);
        }

        $request->session()->regenerate();

        return (new LearnerResource($request->user()))->response();
    }
}
