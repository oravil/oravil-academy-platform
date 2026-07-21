<?php

namespace App\Infrastructure\LearningPath;

use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\LearningPath\LessonContent;
use App\Application\LearningPath\ModuleContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseModuleRepository implements ModuleRepository
{
    public function findWithLessons(string $moduleId): ?ModuleContent
    {
        // Guard against malformed ids reaching Postgres as a uuid literal
        // (architectural constraint: raw database errors must never
        // propagate to the API response — OA-MVP-008 §Architectural
        // Constraints 14). A malformed id is simply not found.
        if (! Str::isUuid($moduleId)) {
            return null;
        }

        $module = DB::table('modules')->where('id', $moduleId)->first();

        if ($module === null) {
            return null;
        }

        $lessons = DB::table('lessons')
            ->where('module_id', $moduleId)
            ->orderBy('position')
            ->get(['id', 'position', 'title']);

        return new ModuleContent(
            moduleId: $module->id,
            title: $module->title,
            purpose: $module->purpose,
            deliverableDescription: $module->deliverable_description,
            lessons: $lessons->map(fn ($lesson): LessonContent => new LessonContent(
                lessonId: $lesson->id,
                position: $lesson->position,
                title: $lesson->title,
            ))->all(),
        );
    }
}
