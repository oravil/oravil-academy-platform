<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\LearnerResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): LearnerResource
    {
        return new LearnerResource($request->user());
    }
}
