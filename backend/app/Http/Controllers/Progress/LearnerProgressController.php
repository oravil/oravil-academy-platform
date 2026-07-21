<?php

namespace App\Http\Controllers\Progress;

use App\Application\Progress\GetProgress;
use App\Http\Controllers\Controller;
use App\Http\Resources\LearnerProgressResource;
use Illuminate\Http\Request;

class LearnerProgressController extends Controller
{
    public function __invoke(Request $request, string $module_id, GetProgress $getProgress): LearnerProgressResource
    {
        $progress = $getProgress->handle($request->user()->id, $module_id);

        return new LearnerProgressResource($progress);
    }
}
