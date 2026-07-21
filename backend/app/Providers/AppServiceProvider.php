<?php

namespace App\Providers;

use App\Application\LearningPath\Contracts\LessonRepository;
use App\Application\LearningPath\Contracts\ModuleRepository;
use App\Application\Progress\Contracts\SubmissionRepository;
use App\Application\Progress\Contracts\SurveyRepository;
use App\Infrastructure\LearningPath\DatabaseLessonRepository;
use App\Infrastructure\LearningPath\DatabaseModuleRepository;
use App\Infrastructure\Progress\DatabaseSubmissionRepository;
use App\Infrastructure\Progress\DatabaseSurveyRepository;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
