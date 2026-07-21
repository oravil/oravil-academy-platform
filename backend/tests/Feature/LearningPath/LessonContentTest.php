<?php

use App\Models\Learner;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function lessonContentFixture(): object
{
    $module = DB::table('modules')->where('slug', 'module-1')->first();
    $lessons = DB::table('lessons')->where('module_id', $module->id)->orderBy('position')->get();
    $assignments = DB::table('assignments')->whereIn('lesson_id', $lessons->pluck('id'))->get()->keyBy('lesson_id');

    return (object) ['module' => $module, 'lessons' => $lessons, 'assignments' => $assignments];
}

describe('GET /v1/lessons/{lesson_id}', function () {
    it('returns the full contract envelope for an available lesson, against real seeded Lesson 1 content', function () {
        $this->seed(ContentSeeder::class);
        $seed = lessonContentFixture();
        $lesson = $seed->lessons[0];
        $assignment = $seed->assignments[$lesson->id];
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson("/v1/lessons/{$lesson->id}")
            ->assertOk()
            ->assertExactJson([
                'lesson_id' => $lesson->id,
                'module_id' => $seed->module->id,
                'position' => 1,
                'title' => $lesson->title,
                'estimated_reading_minutes' => $lesson->estimated_reading_minutes,
                'content' => $lesson->content,
                'assignment' => [
                    'assignment_id' => $assignment->id,
                    'deliverable_name' => $assignment->deliverable_name,
                    'prompt' => $assignment->prompt,
                    'minimum_word_count' => $assignment->minimum_word_count,
                ],
            ]);
    });

    it('returns the approved 403 forbidden envelope for a locked lesson on fresh state', function () {
        $this->seed(ContentSeeder::class);
        $seed = lessonContentFixture();
        $lockedLesson = $seed->lessons[1];
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson("/v1/lessons/{$lockedLesson->id}")
            ->assertStatus(403)
            ->assertExactJson([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'This lesson is locked.',
                ],
            ]);
    });

    it('returns 200 for a complete lesson once its assignment has been submitted', function () {
        $this->seed(ContentSeeder::class);
        $seed = lessonContentFixture();
        $lesson = $seed->lessons[0];
        $assignment = $seed->assignments[$lesson->id];
        $learner = Learner::factory()->create();

        DB::table('assignment_submissions')->insert([
            'id' => (string) Str::uuid(),
            'learner_id' => $learner->id,
            'assignment_id' => $assignment->id,
            'content' => 'A completed reflection.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->getJson("/v1/lessons/{$lesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson_id', $lesson->id)
            ->assertJsonPath('content', $lesson->content);
    });

    it('returns the approved 401 error contract for an unauthenticated request', function () {
        $this->seed(ContentSeeder::class);
        $seed = lessonContentFixture();

        $this->getJson("/v1/lessons/{$seed->lessons[0]->id}")
            ->assertStatus(401)
            ->assertExactJson([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication required.',
                ],
            ]);
    });

    it('returns 404 for an unknown lesson id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson('/v1/lessons/'.(string) Str::uuid())
            ->assertStatus(404)
            ->assertExactJson([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Lesson not found.',
                ],
            ]);
    });

    it('returns 404 rather than a raw database error for a malformed lesson id', function () {
        $learner = Learner::factory()->create();

        $this->actingAs($learner)
            ->getJson('/v1/lessons/not-a-uuid')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');
    });
});
