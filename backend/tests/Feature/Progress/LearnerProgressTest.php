<?php

use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function learnerProgressFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get(['id', 'position', 'title']);

    return (object) ['module' => $module, 'lessons' => $lessons];
}

describe('GET /v1/learners/me/progress/{module_id}', function () {
    it('returns the first-access state with the exact OA-MVP-007 envelope: 0 complete, current lesson 1, in progress, survey not submitted', function () {
        $this->seed(ContentSeeder::class);
        $seed = learnerProgressFixture();
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertExactJson([
                'module_id' => $seed->module->id,
                'lessons_complete' => 0,
                'lessons_total' => 4,
                'current_lesson_id' => $seed->lessons[0]->id,
                'module_status' => 'in_progress',
                'survey_submitted' => false,
            ]);
    });

    it('reflects one submitted lesson: lessons_complete advances to 1 and current_lesson_id advances to lesson 2 — sanity check for immediate-prior-only availability on a read endpoint', function () {
        $this->seed(ContentSeeder::class);
        $seed = learnerProgressFixture();
        $learner = Learner::factory()->create();

        $assignmentId = DB::table('assignments')->where('lesson_id', $seed->lessons[0]->id)->value('id');
        DB::table('assignment_submissions')->insert([
            'id' => (string) Str::uuid(),
            'learner_id' => $learner->id,
            'assignment_id' => $assignmentId,
            'content' => 'A completed reflection.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertJsonPath('lessons_complete', 1)
            ->assertJsonPath('current_lesson_id', $seed->lessons[1]->id)
            ->assertJsonPath('module_status', 'in_progress');
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = learnerProgressFixture();

        $this->getJson("/v1/learners/me/progress/{$seed->module->id}")
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
            ->getJson('/v1/learners/me/progress/'.(string) Str::uuid())
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
            ->getJson('/v1/learners/me/progress/not-a-uuid')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });
});
