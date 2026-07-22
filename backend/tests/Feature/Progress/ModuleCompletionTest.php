<?php

use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function moduleCompletionFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get(['id', 'position', 'title']);
    $assignments = DB::table('assignments')->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');

    return (object) ['module' => $module, 'lessons' => $lessons, 'assignments' => $assignments];
}

function completeModuleFor(string $learnerId, object $seed): void
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

describe('GET /v1/modules/{module_id}/completion', function () {
    it('returns the exact OA-MVP-007 envelope for a learner who has completed all four lessons', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleCompletionFixture();
        $learner = Learner::factory()->create();
        completeModuleFor($learner->id, $seed);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/completion")
            ->assertOk()
            ->assertExactJson([
                'module_id' => $seed->module->id,
                'title' => $seed->module->title,
                'deliverable_description' => $seed->module->deliverable_description,
                'completed_lessons' => $seed->lessons->map(fn ($lesson) => [
                    'lesson_id' => $lesson->id,
                    'position' => $lesson->position,
                    'title' => $lesson->title,
                ])->all(),
                'survey_submitted' => false,
            ]);
    });

    it('returns 403 forbidden for a learner who has not yet completed the module', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleCompletionFixture();
        $learner = Learner::factory()->create();

        // Zero submissions — a fresh learner, distinct from the completed
        // learner above (the second-learner need flagged at VS-004 close).
        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/completion")
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
        $seed = moduleCompletionFixture();
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
            ->getJson("/v1/modules/{$seed->module->id}/completion")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleCompletionFixture();

        $this->getJson("/v1/modules/{$seed->module->id}/completion")
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
            ->getJson('/v1/modules/'.(string) Str::uuid().'/completion')
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
            ->getJson('/v1/modules/not-a-uuid/completion')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });
});
