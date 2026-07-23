<?php

use App\Infrastructure\Survey\DatabaseSurveyContentRepository;
use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function surveyResponseFixture(): object
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
        // Seeded order (OA-MVP-004): position 1 rating/required, position 2
        // text/required, position 3 text/optional.
        'rating' => $questions[0],
        'requiredText' => $questions[1],
        'optionalText' => $questions[2],
    ];
}

function completeModuleForSurveyResponse(string $learnerId, object $seed): void
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

function fullSurveyAnswers(object $seed): array
{
    return [
        ['survey_question_id' => $seed->rating->id, 'answer_rating' => 5],
        ['survey_question_id' => $seed->requiredText->id, 'answer_text' => 'More real-world case studies would help.'],
        ['survey_question_id' => $seed->optionalText->id, 'answer_text' => 'No further comments.'],
    ];
}

describe('POST /v1/surveys/{survey_id}/responses', function () {
    it('submits all three answers through the real write path and flips survey_submitted on progress and completion', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        expect(DB::table('survey_responses')->count())->toBe(0);

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertJsonPath('survey_submitted', false);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/completion")
            ->assertOk()
            ->assertJsonPath('survey_submitted', false);

        $response = $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", ['answers' => fullSurveyAnswers($seed)]);

        $response->assertCreated()
            ->assertJsonPath('survey_id', $seed->survey->id)
            ->assertJsonStructure(['survey_id', 'submitted_at']);

        expect(DB::table('survey_responses')->where('learner_id', $learner->id)->count())->toBe(3);

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertJsonPath('survey_submitted', true);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/completion")
            ->assertOk()
            ->assertJsonPath('survey_submitted', true);
    });

    it('submits successfully when the optional question is omitted', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [
                'answers' => [
                    ['survey_question_id' => $seed->rating->id, 'answer_rating' => 4],
                    ['survey_question_id' => $seed->requiredText->id, 'answer_text' => 'Good pacing overall.'],
                ],
            ])
            ->assertCreated();

        expect(DB::table('survey_responses')->where('learner_id', $learner->id)->count())->toBe(2);
    });

    it('returns 403 forbidden when the module is not yet complete', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", ['answers' => fullSurveyAnswers($seed)])
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This module is not yet complete.',
                ],
            ]);

        expect(DB::table('survey_responses')->count())->toBe(0);
    });

    it('returns 403 forbidden on a second submission and creates no additional rows', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", ['answers' => fullSurveyAnswers($seed)])
            ->assertCreated();

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", ['answers' => fullSurveyAnswers($seed)])
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This survey has already been submitted.',
                ],
            ]);

        expect(DB::table('survey_responses')->where('learner_id', $learner->id)->count())->toBe(3);
    });

    it('returns 422 when a required question has no answer', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [
                'answers' => [
                    ['survey_question_id' => $seed->rating->id, 'answer_rating' => 5],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        expect(DB::table('survey_responses')->count())->toBe(0);
    });

    it('returns 422 when a rating answer is out of range', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [
                'answers' => [
                    ['survey_question_id' => $seed->rating->id, 'answer_rating' => 7],
                    ['survey_question_id' => $seed->requiredText->id, 'answer_text' => 'Fine.'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        expect(DB::table('survey_responses')->count())->toBe(0);
    });

    it('returns 422 when a required text answer is empty', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [
                'answers' => [
                    ['survey_question_id' => $seed->rating->id, 'answer_rating' => 5],
                    ['survey_question_id' => $seed->requiredText->id, 'answer_text' => '   '],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        expect(DB::table('survey_responses')->count())->toBe(0);
    });

    it('returns 422 when a survey_question_id does not belong to the survey', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [
                'answers' => [
                    ['survey_question_id' => (string) Str::uuid(), 'answer_rating' => 5],
                    ['survey_question_id' => $seed->requiredText->id, 'answer_text' => 'Fine.'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        expect(DB::table('survey_responses')->count())->toBe(0);
    });

    it('returns 422 when answers is missing', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        completeModuleForSurveyResponse($learner->id, $seed);

        $this->actingAs($learner)
            ->postJson("/v1/surveys/{$seed->survey->id}/responses", [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();

        $this->postJson("/v1/surveys/{$seed->survey->id}/responses", ['answers' => fullSurveyAnswers($seed)])
            ->assertStatus(401)
            ->assertExactJson([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ],
            ]);
    });

    it('returns 404 for an unknown survey id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson('/v1/surveys/'.(string) Str::uuid().'/responses', ['answers' => [['survey_question_id' => (string) Str::uuid(), 'answer_rating' => 5]]])
            ->assertStatus(404)
            ->assertExactJson([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Survey not found.',
                ],
            ]);
    });

    it('returns 404 rather than a raw database error for a malformed survey id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson('/v1/surveys/not-a-uuid/responses', ['answers' => [['survey_question_id' => (string) Str::uuid(), 'answer_rating' => 5]]])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });

    it('translates a racing duplicate insert into the same UniqueConstraintViolationException the use case catches as its backstop', function () {
        // Mirrors AssignmentSubmissionTest's equivalent race-backstop proof:
        // this proves the database constraint really throws the exact
        // exception type SubmitSurveyResponse's catch clause targets. It
        // does NOT exercise the full HTTP race window (two concurrent
        // requests both passing the hasSubmittedResponse pre-check before
        // either commits) — not reproducible in Pest's synchronous,
        // single-process test runner. Stated explicitly rather than
        // silently skipped.
        $this->seed(ContentSeeder::class);
        $seed = surveyResponseFixture();
        $learner = Learner::factory()->create();
        $repository = new DatabaseSurveyContentRepository;

        $repository->createResponses($seed->survey->id, $learner->id, [
            ['survey_question_id' => $seed->rating->id, 'answer_text' => null, 'answer_rating' => 5],
        ]);

        expect(fn () => $repository->createResponses($seed->survey->id, $learner->id, [
            ['survey_question_id' => $seed->rating->id, 'answer_text' => null, 'answer_rating' => 4],
        ]))->toThrow(UniqueConstraintViolationException::class);
    });
});
