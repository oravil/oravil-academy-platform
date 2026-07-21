<?php

use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function moduleOverviewFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get(['id', 'position', 'title']);

    return (object) ['module' => $module, 'lessons' => $lessons];
}

describe('GET /v1/modules/{module_id}/overview', function () {
    it('returns the first-access state with the exact OA-MVP-007 envelope: lesson 1 available, 2-4 locked, module in progress', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleOverviewFixture();
        $learner = Learner::factory()->create();

        expect($seed->module->purpose)->not->toBeNull();

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/overview")
            ->assertOk()
            ->assertExactJson([
                'module_id' => $seed->module->id,
                'title' => $seed->module->title,
                'purpose' => $seed->module->purpose,
                'deliverable_description' => $seed->module->deliverable_description,
                'lessons' => [
                    ['lesson_id' => $seed->lessons[0]->id, 'position' => 1, 'title' => $seed->lessons[0]->title, 'status' => 'available'],
                    ['lesson_id' => $seed->lessons[1]->id, 'position' => 2, 'title' => $seed->lessons[1]->title, 'status' => 'locked'],
                    ['lesson_id' => $seed->lessons[2]->id, 'position' => 3, 'title' => $seed->lessons[2]->title, 'status' => 'locked'],
                    ['lesson_id' => $seed->lessons[3]->id, 'position' => 4, 'title' => $seed->lessons[3]->title, 'status' => 'locked'],
                ],
                'module_status' => 'in_progress',
            ]);
    });

    it('reflects lesson 1 completing and lesson 2 unlocking once a submission exists — sanity check for immediate-prior-only availability on a read endpoint', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleOverviewFixture();
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
            ->getJson("/v1/modules/{$seed->module->id}/overview")
            ->assertOk()
            ->assertJsonPath('lessons.0.status', 'complete')
            ->assertJsonPath('lessons.1.status', 'available')
            ->assertJsonPath('lessons.2.status', 'locked')
            ->assertJsonPath('lessons.3.status', 'locked')
            ->assertJsonPath('module_status', 'in_progress');
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = moduleOverviewFixture();

        $this->getJson("/v1/modules/{$seed->module->id}/overview")
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
            ->getJson('/v1/modules/'.(string) Str::uuid().'/overview')
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
            ->getJson('/v1/modules/not-a-uuid/overview')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });
});
