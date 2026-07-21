<?php

use App\Domain\Progress\LessonStatus;
use App\Domain\Progress\ModuleStatus;
use App\Domain\Progress\ModuleStatusRule;

test('module is in progress on fresh state with no lessons complete', function (): void {
    $status = (new ModuleStatusRule)->apply([
        LessonStatus::Available,
        LessonStatus::Locked,
        LessonStatus::Locked,
        LessonStatus::Locked,
    ]);

    expect($status)->toBe(ModuleStatus::InProgress);
});

test('module is in progress while any lesson is not complete, including the last one pending', function (): void {
    $status = (new ModuleStatusRule)->apply([
        LessonStatus::Complete,
        LessonStatus::Complete,
        LessonStatus::Complete,
        LessonStatus::Available,
    ]);

    expect($status)->toBe(ModuleStatus::InProgress);
});

test('module becomes complete once the last lesson completes', function (): void {
    $status = (new ModuleStatusRule)->apply([
        LessonStatus::Complete,
        LessonStatus::Complete,
        LessonStatus::Complete,
        LessonStatus::Complete,
    ]);

    expect($status)->toBe(ModuleStatus::Complete);
});

test('a single-lesson module is complete once that lesson completes', function (): void {
    expect((new ModuleStatusRule)->apply([LessonStatus::Available]))->toBe(ModuleStatus::InProgress);
    expect((new ModuleStatusRule)->apply([LessonStatus::Complete]))->toBe(ModuleStatus::Complete);
});

test('an empty lesson list is vacuously complete', function (): void {
    // No lessons to leave incomplete. Not a realistic Version 0.1 state (every
    // module has lessons) but the rule must resolve deterministically for any
    // input it is given.
    expect((new ModuleStatusRule)->apply([]))->toBe(ModuleStatus::Complete);
});
