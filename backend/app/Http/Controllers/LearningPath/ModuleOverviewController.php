<?php

namespace App\Http\Controllers\LearningPath;

use App\Application\LearningPath\GetModuleOverview;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModuleOverviewResource;
use Illuminate\Http\Request;

class ModuleOverviewController extends Controller
{
    public function __invoke(Request $request, string $module_id, GetModuleOverview $getModuleOverview): ModuleOverviewResource
    {
        $overview = $getModuleOverview->handle($request->user()->id, $module_id);

        return new ModuleOverviewResource($overview);
    }
}
