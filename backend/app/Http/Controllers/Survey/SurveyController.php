<?php

namespace App\Http\Controllers\Survey;

use App\Application\Survey\GetSurvey;
use App\Http\Controllers\Controller;
use App\Http\Resources\SurveyResource;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __invoke(Request $request, string $module_id, GetSurvey $getSurvey): SurveyResource
    {
        $survey = $getSurvey->handle($request->user()->id, $module_id);

        return new SurveyResource($survey);
    }
}
