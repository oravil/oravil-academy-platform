<?php

use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function surveyFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get(['id', 'position', 'title']);
    $assignments = DB::table('assignments')->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');
    $survey = DB::table('surveys')->where('module_id', $module->id)->first(['id', 'module_id', 'title']);
    $questions = DB::table('survey_questions')->where('survey_id', $survey->id)->orderBy('position')->get();

    return (object) [
        'module' => $module,
        'lessons' => $lessons,
        'assignments' => $assignments,
        'survey' => $survey,
        'questions' => $questions,
    ];
}

function completeModuleForSurvey(string $learnerId, object $seed): void
{
    foreach ($seed->lessons as $lesson) {
        DB::table('assignment_submissions')->insert([
            'id' => (string) Str::uuid(),
            'learner_id' => $learnerId,
            'assignment_id' => $seed->assignments[$lesson->id]->id,
            'content' => "A completed reflection for lesson {$lesson->position}.",
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }
}

describe('GET /v1/modules/{module_id}/survey', function () {
    it('returns the exact OA-MVP-007 envelope for a learner who has completed the module', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurvey($learner->id, $seed);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/survey")
            ->assertOk()
            ->assertExactJson([
                'survey_id' => $seed->survey->id,
                'module_id' => $seed->module->id,
                'title' => $seed->survey->title,
                'questions' => $seed->questions->map(fn ($question) => [
                    'survey_question_id' => $question->id,
                    'position' => $question->position,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'required' => $question->required,
                ])->all(),
            ]);
    });

    it('returns 403 forbidden for a learner who has not yet completed the module', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyFixture();
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/survey")
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This module is not yet complete.',
                ],
            ]);
    });

    it('returns 403 forbidden when only some lessons are complete', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyFixture();
        $learner = Learner::factory()->create();

        DB::table('assignment_submissions')->insert([
            'id' => (string) Str::uuid(),
            'learner_id' => $learner->id,
            'assignment_id' => $seed->assignments[$seed->lessons[0]->id]->id,
            'content' => 'Only the first lesson is complete.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/survey")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    });

    it('returns 403 forbidden when the learner has already submitted the survey', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurvey($learner->id, $seed);

        DB::table('survey_responses')->insert([
            'id' => (string) Str::uuid(),
            'learner_id' => $learner->id,
            'survey_question_id' => $seed->questions[0]->id,
            'answer_rating' => 5,
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/survey")
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This survey has already been submitted.',
                ],
            ]);
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyFixture();

        $this->getJson("/v1/modules/{$seed->module->id}/survey")
            ->assertStatus(401)
            ->assertExactJson([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ],
            ]);
    });

    it('returns 404 for an unknown module id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson('/v1/modules/'.(string) Str::uuid().'/survey')
            ->assertStatus(404)
            ->assertExactJson([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Module not found.',
                ],
            ]);
    });

    it('returns 404 rather than a raw database error for a malformed module id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson('/v1/modules/not-a-uuid/survey')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });
});
