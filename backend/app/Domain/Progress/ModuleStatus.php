<?php

namespace App\Domain\Progress;

enum ModuleStatus: string
{
    case InProgress = 'in_progress';
    case Complete = 'complete';
}
