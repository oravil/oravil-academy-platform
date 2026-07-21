<?php

namespace App\Domain\Progress;

/**
 * OA-MVP-005 Domain Rule 5: a module is complete only when Assignment
 * Submissions exist for all lessons in that module.
 */
final class ModuleStatusRule
{
    /**
     * @param  LessonStatus[]  $lessonStatuses  All lesson statuses for the module, any order.
     */
    public function apply(array $lessonStatuses): ModuleStatus
    {
        foreach ($lessonStatuses as $status) {
            if ($status !== LessonStatus::Complete) {
                return ModuleStatus::InProgress;
            }
        }

        return ModuleStatus::Complete;
    }
}
