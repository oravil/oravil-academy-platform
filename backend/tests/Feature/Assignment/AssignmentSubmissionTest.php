<?php

use App\Infrastructure\Assignment\DatabaseAssignmentRepository;
use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function assignmentSubmissionFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get(['id', 'position', 'title']);
    $assignments = DB::table('assignments')->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');

    return (object) ['module' => $module, 'lessons' => $lessons, 'assignments' => $assignments];
}

describe('POST /v1/assignments/{assignment_id}/submissions', function () {
    it('submits lesson 1\'s assignment through the real write path and unlocks lesson 2 end to end', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];

        // No seeded submission rows anywhere — the write endpoint does the work.
        expect(DB::table('assignment_submissions')->count())->toBe(0);

        $response = $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", [
                'content' => 'A thorough, thoughtful reflection on digital marketing fundamentals.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('assignment_id', $assignment1->id)
            ->assertJsonPath('status', 'submitted')
            ->assertJsonStructure(['submission_id', 'assignment_id', 'status', 'submitted_at']);

        expect(DB::table('assignment_submissions')->where('assignment_id', $assignment1->id)->count())->toBe(1);

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/overview")
            ->assertOk()
            ->assertJsonPath('lessons.0.status', 'complete')
            ->assertJsonPath('lessons.1.status', 'available')
            ->assertJsonPath('lessons.2.status', 'locked')
            ->assertJsonPath('lessons.3.status', 'locked');

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertJsonPath('lessons_complete', 1)
            ->assertJsonPath('current_lesson_id', $seed->lessons[1]->id)
            ->assertJsonPath('module_status', 'in_progress');
    });

    it('advances the sequence one link at a time after submitting lessons 1 and 2', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];
        $assignment2 = $seed->assignments[$seed->lessons[1]->id];

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => 'Lesson 1 reflection, complete and thoughtful.'])
            ->assertCreated();

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment2->id}/submissions", ['content' => 'Lesson 2 reflection, complete and thoughtful.'])
            ->assertCreated();

        $this->actingAs($learner)
            ->getJson("/v1/modules/{$seed->module->id}/overview")
            ->assertOk()
            ->assertJsonPath('lessons.0.status', 'complete')
            ->assertJsonPath('lessons.1.status', 'complete')
            ->assertJsonPath('lessons.2.status', 'available')
            ->assertJsonPath('lessons.3.status', 'locked');

        $this->actingAs($learner)
            ->getJson("/v1/learners/me/progress/{$seed->module->id}")
            ->assertOk()
            ->assertJsonPath('lessons_complete', 2)
            ->assertJsonPath('current_lesson_id', $seed->lessons[2]->id)
            ->assertJsonPath('module_status', 'in_progress');
    });

    it('returns 403 forbidden when submitting an assignment whose lesson is still locked', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $lockedAssignment = $seed->assignments[$seed->lessons[1]->id];

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$lockedAssignment->id}/submissions", ['content' => 'An attempt to skip ahead.'])
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This assignment is not yet unlocked.',
                ],
            ]);

        expect(DB::table('assignment_submissions')->count())->toBe(0);
    });

    it('returns 403 forbidden on a second submission for the same assignment and creates no second row', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => 'First submission, thoughtful and complete.'])
            ->assertCreated();

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => 'Second attempt at the same assignment.'])
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This assignment has already been submitted.',
                ],
            ]);

        expect(DB::table('assignment_submissions')->where('assignment_id', $assignment1->id)->count())->toBe(1);
    });

    it('returns 422 for empty or whitespace-only content', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => "   \n  "])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonFragment([
                'field' => 'content',
                'message' => 'Your assignment is empty. Please write your response before submitting.',
            ]);

        expect(DB::table('assignment_submissions')->count())->toBe(0);
    });

    it('returns 422 when content is below the assignment\'s minimum word count', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $learner = Learner::factory()->create();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];

        // Seeded assignments all have a null minimum_word_count — build a
        // concrete minimum for this scenario (no schema change; this only
        // sets an existing nullable column on an existing row).
        DB::table('assignments')->where('id', $assignment1->id)->update(['minimum_word_count' => 20]);

        $this->actingAs($learner)
            ->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => 'Too short.'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonFragment([
                'field' => 'content',
                'message' => 'Your response is shorter than the minimum required. Please complete your answer.',
            ]);

        expect(DB::table('assignment_submissions')->count())->toBe(0);
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = assignmentSubmissionFixture();
        $assignment1 = $seed->assignments[$seed->lessons[0]->id];

        $this->postJson("/v1/assignments/{$assignment1->id}/submissions", ['content' => 'Attempted while logged out.'])
            ->assertStatus(401)
            ->assertExactJson([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ],
            ]);
    });

    it('returns 404 for an unknown assignment id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson('/v1/assignments/'.(string) Str::uuid().'/submissions', ['content' => 'Content for a non-existent assignment.'])
            ->assertStatus(404)
            ->assertExactJson([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Assignment not found.',
                ],
            ]);
    });

    it('returns 404 rather than a raw database error for a malformed assignment id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->postJson('/v1/assignments/not-a-uuid/submissions', ['content' => 'Content for a malformed id.'])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });

    it('translates a racing duplicate insert into the same UniqueConstraintViolationException the use case catches as its backstop', function () {
        // This proves the database constraint the backstop relies on really
        // throws the exact exception type SubmitAssignment's catch clause
        // targets. It does NOT exercise the full HTTP race window (two
        // concurrent requests both passing the status pre-check before
        // either commits) — that is not reproducible in Pest's synchronous,
        // single-process test runner, and ModuleProgressService is `final`,
        // so it cannot be mocked to force the use case past its own
        // pre-check while a submission row already exists. Stated explicitly
        // rather than silently skipped, per instruction.
        $this->seed(ContentSeeder::class);
        $learner = Learner::factory()->create();
        $assignmentId = DB::table('assignments')->orderBy('id')->value('id');
        $repository = new DatabaseAssignmentRepository;

        $repository->createSubmission($learner->id, $assignmentId, 'First write.');

        expect(fn () => $repository->createSubmission($learner->id, $assignmentId, 'Second write racing the first.'))
            ->toThrow(UniqueConstraintViolationException::class);
    });
});
