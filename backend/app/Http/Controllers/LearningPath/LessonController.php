<?php

namespace App\Http\Controllers\LearningPath;

use App\Application\LearningPath\GetLessonContent;
use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __invoke(Request $request, string $lesson_id, GetLessonContent $getLessonContent): LessonResource
    {
        $lesson = $getLessonContent->handle($request->user()->id, $lesson_id);

        return new LessonResource($lesson);
    }
}
