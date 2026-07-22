<?php

namespace App\Http\Controllers\Assignment;

use App\Application\Assignment\SubmitAssignment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assignment\SubmitAssignmentRequest;
use App\Http\Resources\AssignmentSubmissionResource;
use Illuminate\Http\JsonResponse;

class AssignmentSubmissionController extends Controller
{
    public function __invoke(SubmitAssignmentRequest $request, string $assignment_id, SubmitAssignment $submitAssignment): JsonResponse
    {
        $submission = $submitAssignment->handle(
            $request->user()->id,
            $assignment_id,
            $request->validated('content'),
        );

        return (new AssignmentSubmissionResource($submission))->response()->setStatusCode(201);
    }
}
