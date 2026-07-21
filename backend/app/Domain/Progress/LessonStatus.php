<?php

namespace App\Domain\Progress;

enum LessonStatus: string
{
    case Locked = 'locked';
    case Available = 'available';
    case Complete = 'complete';
}
