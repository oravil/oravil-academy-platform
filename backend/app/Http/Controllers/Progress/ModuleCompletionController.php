<?php

namespace App\Http\Controllers\Progress;

use App\Application\Progress\GetModuleCompletion;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleCompletionResource;
use Illuminate\Http\Request;

class ModuleCompletionController extends Controller
{
    public function __invoke(Request $request, string $module_id, GetModuleCompletion $getModuleCompletion): ModuleCompletionResource
    {
        $completion = $getModuleCompletion->handle($request->user()->id, $module_id);

        return new ModuleCompletionResource($completion);
    }
}
