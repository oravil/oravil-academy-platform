<?php

use App\Domain\Progress\LessonProgressInput;
use App\Domain\Progress\LessonStatus;
use App\Domain\Progress\LessonStatusRule;

function lessonInput(string $id, bool $submitted): LessonProgressInput
{
    return new LessonProgressInput($id, $submitted);
}

test('first lesson is available on fresh state with no submissions', function (): void {
    $statuses = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', false),
        lessonInput('lesson-2', false),
        lessonInput('lesson-3', false),
        lessonInput('lesson-4', false),
    ]);

    expect($statuses)->toBe([
        'lesson-1' => LessonStatus::Available,
        'lesson-2' => LessonStatus::Locked,
        'lesson-3' => LessonStatus::Locked,
        'lesson-4' => LessonStatus::Locked,
    ]);
});

test('completing lesson 1 unlocks lesson 2 while later lessons stay locked', function (): void {
    $statuses = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', true),
        lessonInput('lesson-2', false),
        lessonInput('lesson-3', false),
        lessonInput('lesson-4', false),
    ]);

    expect($statuses)->toBe([
        'lesson-1' => LessonStatus::Complete,
        'lesson-2' => LessonStatus::Available,
        'lesson-3' => LessonStatus::Locked,
        'lesson-4' => LessonStatus::Locked,
    ]);
});

test('a middle lesson transitions from locked to available as the prior lesson completes', function (): void {
    $beforePriorSubmission = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', false),
        lessonInput('lesson-2', false),
    ]);
    expect($beforePriorSubmission['lesson-2'])->toBe(LessonStatus::Locked);

    $afterPriorSubmission = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', true),
        lessonInput('lesson-2', false),
    ]);
    expect($afterPriorSubmission['lesson-2'])->toBe(LessonStatus::Available);
});

test('completing the last lesson marks it complete with no lesson left locked or available', function (): void {
    $statuses = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', true),
        lessonInput('lesson-2', true),
        lessonInput('lesson-3', true),
        lessonInput('lesson-4', true),
    ]);

    expect($statuses)->toBe([
        'lesson-1' => LessonStatus::Complete,
        'lesson-2' => LessonStatus::Complete,
        'lesson-3' => LessonStatus::Complete,
        'lesson-4' => LessonStatus::Complete,
    ]);
});

test('a single-lesson module is available before submission and complete after', function (): void {
    $fresh = (new LessonStatusRule)->apply([lessonInput('lesson-1', false)]);
    expect($fresh['lesson-1'])->toBe(LessonStatus::Available);

    $submitted = (new LessonStatusRule)->apply([lessonInput('lesson-1', true)]);
    expect($submitted['lesson-1'])->toBe(LessonStatus::Complete);
});

test('an empty lesson list returns an empty status map', function (): void {
    expect((new LessonStatusRule)->apply([]))->toBe([]);
});

test('a lesson with its own submission is complete even when a prior lesson is not, per Domain Rule 4', function (): void {
    // OA-MVP-005 Rule 4 defines "complete" solely in terms of the lesson's own
    // submission. Out-of-order submissions should not occur via the write path
    // (submitting an assignment for a locked lesson is rejected upstream), but
    // this read-side rule must still resolve deterministically if the data
    // ever diverges — completeness is never downgraded by a predecessor's state.
    $statuses = (new LessonStatusRule)->apply([
        lessonInput('lesson-1', false),
        lessonInput('lesson-2', true),
        lessonInput('lesson-3', false),
    ]);

    expect($statuses)->toBe([
        'lesson-1' => LessonStatus::Available,
        'lesson-2' => LessonStatus::Complete,
        'lesson-3' => LessonStatus::Available,
    ]);
});
