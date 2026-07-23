<?php

namespace App\Providers;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\LearningPath\Contracts\LessonRepository;
use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\Progress\Contracts\SubmissionRepository;
use App\Application\Progress\Contracts\SurveyRepository;
use App\Application\Survey\Contracts\SurveyContentRepository;
use App\Infrastructure\Assignment\DatabaseAssignmentRepository;
use App\Infrastructure\LearningPath\DatabaseLessonRepository;
use App\Infrastructure\LearningPath\DatabaseModuleRepository;
use App\Infrastructure\Progress\DatabaseSubmissionRepository;
use App\Infrastructure\Progress\DatabaseSurveyRepository;
use App\Infrastructure\Survey\DatabaseSurveyContentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ModuleRepository::class, DatabaseModuleRepository::class);
        $this->app->bind(LessonRepository::class, DatabaseLessonRepository::class);
        $this->app->bind(SubmissionRepository::class, DatabaseSubmissionRepository::class);
        $this->app->bind(SurveyRepository::class, DatabaseSurveyRepository::class);
        $this->app->bind(AssignmentRepository::class, DatabaseAssignmentRepository::class);
        $this->app->bind(SurveyContentRepository::class, DatabaseSurveyContentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
