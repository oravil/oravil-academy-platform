<?php

namespace App\Http\Controllers\Survey;

use App\Application\Survey\SubmitSurveyResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\SubmitSurveyResponseRequest;
use App\Http\Resources\SurveyResponseResource;
use Illuminate\Http\JsonResponse;

class SurveyResponseController extends Controller
{
    public function __invoke(SubmitSurveyResponseRequest $request, string $survey_id, SubmitSurveyResponse $submitSurveyResponse): JsonResponse
    {
        $result = $submitSurveyResponse->handle(
            $request->user()->id,
            $survey_id,
            $request->validated('answers'),
        );

        return (new SurveyResponseResource($result))->response()->setStatusCode(201);
    }
}
