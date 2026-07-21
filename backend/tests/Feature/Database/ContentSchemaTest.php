<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Returns column metadata (name, data_type, column_default, is_nullable) for a table, keyed by column_name.
 */
function contentSchemaColumns(string $table): Collection
{
    return collect(DB::select(<<<'SQL'
        SELECT column_name, data_type, column_default, is_nullable
        FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = ?
        ORDER BY ordinal_position
        SQL, [$table]))->keyBy('column_name');
}

/**
 * Inserts a full valid content fixture chain (learning_path -> phase -> module -> lesson -> assignment,
 * plus a survey -> survey_question on the same module, and a learner) and returns all generated ids.
 */
function contentSchemaFixture(): array
{
    $learningPathId = DB::table('learning_paths')->insertGetId([
        'slug' => 'digital-marketing',
        'title' => 'Digital Marketing',
    ], 'id');

    $phaseId = DB::table('phases')->insertGetId([
        'learning_path_id' => $learningPathId,
        'slug' => 'phase-0-foundations',
        'title' => 'Phase 0 — Foundations',
        'position' => 1,
    ], 'id');

    $moduleId = DB::table('modules')->insertGetId([
        'phase_id' => $phaseId,
        'slug' => 'module-1',
        'title' => 'Module 1 — The Digital Marketing Landscape',
        'position' => 1,
        'deliverable_description' => 'A short written reflection.',
    ], 'id');

    $lessonId = DB::table('lessons')->insertGetId([
        'module_id' => $moduleId,
        'slug' => 'lesson-1',
        'title' => 'What Is Digital Marketing',
        'position' => 1,
        'content' => 'Lesson content.',
    ], 'id');

    $assignmentId = DB::table('assignments')->insertGetId([
        'lesson_id' => $lessonId,
        'prompt' => 'Reflect on the lesson.',
        'deliverable_name' => 'Reflection',
    ], 'id');

    $surveyId = DB::table('surveys')->insertGetId([
        'module_id' => $moduleId,
        'title' => 'Module 1 Survey',
    ], 'id');

    $surveyQuestionId = DB::table('survey_questions')->insertGetId([
        'survey_id' => $surveyId,
        'position' => 1,
        'question_text' => 'How would you rate this module?',
        'question_type' => 'rating',
    ], 'id');

    $learnerId = DB::table('learners')->insertGetId([
        'email' => Str::random(12).'@example.com',
        'display_name' => 'Schema Learner',
        'password_hash' => 'not-a-real-password-hash',
    ], 'id');

    return compact(
        'learningPathId',
        'phaseId',
        'moduleId',
        'lessonId',
        'assignmentId',
        'surveyId',
        'surveyQuestionId',
        'learnerId',
    );
}

describe('learning_paths schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        expect(Schema::hasTable('learning_paths'))->toBeTrue();

        $columns = contentSchemaColumns('learning_paths');

        expect($columns->keys()->all())->toBe(['id', 'slug', 'title', 'created_at', 'updated_at'])
            ->and($columns['id']->data_type)->toBe('uuid')
            ->and($columns['id']->column_default)->toBe('gen_random_uuid()')
            ->and($columns['slug']->data_type)->toBe('text')
            ->and($columns['title']->data_type)->toBe('text')
            ->and($columns['created_at']->column_default)->toBe('now()')
            ->and($columns['updated_at']->column_default)->toBe('now()')
            ->and($columns->pluck('is_nullable')->unique()->all())->toBe(['NO']);
    });

    it('enforces a unique slug', function () {
        DB::table('learning_paths')->insert(['slug' => 'digital-marketing', 'title' => 'Digital Marketing']);

        expect(fn () => DB::table('learning_paths')->insert(['slug' => 'digital-marketing', 'title' => 'Duplicate']))
            ->toThrow(QueryException::class);
    });
});

describe('phases schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('phases');

        expect($columns->keys()->all())->toBe([
            'id', 'learning_path_id', 'slug', 'title', 'position', 'created_at', 'updated_at',
        ])->and($columns['learning_path_id']->data_type)->toBe('uuid')
            ->and($columns['position']->data_type)->toBe('integer')
            ->and($columns['position']->is_nullable)->toBe('NO');
    });

    it('rejects a phase referencing a non-existent learning path', function () {
        expect(fn () => DB::table('phases')->insert([
            'learning_path_id' => (string) Str::uuid(),
            'slug' => 'orphan-phase',
            'title' => 'Orphan Phase',
            'position' => 1,
        ]))->toThrow(QueryException::class);
    });

    it('enforces unique position and slug within a learning path', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('phases')->insert([
            'learning_path_id' => $fixture['learningPathId'],
            'slug' => 'phase-0-foundations-duplicate-position',
            'title' => 'Duplicate Position',
            'position' => 1,
        ]))->toThrow(QueryException::class);

        expect(fn () => DB::table('phases')->insert([
            'learning_path_id' => $fixture['learningPathId'],
            'slug' => 'phase-0-foundations',
            'title' => 'Duplicate Slug',
            'position' => 2,
        ]))->toThrow(QueryException::class);
    });

    it('rejects a non-positive position', function () {
        $learningPathId = DB::table('learning_paths')->insertGetId([
            'slug' => 'digital-marketing',
            'title' => 'Digital Marketing',
        ], 'id');

        expect(fn () => DB::table('phases')->insert([
            'learning_path_id' => $learningPathId,
            'slug' => 'phase-zero',
            'title' => 'Phase Zero',
            'position' => 0,
        ]))->toThrow(QueryException::class);
    });
});

describe('modules schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('modules');

        expect($columns->keys()->all())->toBe([
            'id', 'phase_id', 'slug', 'title', 'position', 'deliverable_description', 'created_at', 'updated_at', 'purpose',
        ])->and($columns['deliverable_description']->is_nullable)->toBe('YES')
            ->and($columns['position']->is_nullable)->toBe('NO')
            ->and($columns['purpose']->is_nullable)->toBe('YES');
    });

    it('rejects a module referencing a non-existent phase', function () {
        expect(fn () => DB::table('modules')->insert([
            'phase_id' => (string) Str::uuid(),
            'slug' => 'orphan-module',
            'title' => 'Orphan Module',
            'position' => 1,
        ]))->toThrow(QueryException::class);
    });

    it('enforces unique position and slug within a phase, and a positive position', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('modules')->insert([
            'phase_id' => $fixture['phaseId'],
            'slug' => 'module-1-duplicate-position',
            'title' => 'Duplicate Position',
            'position' => 1,
        ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('modules')->insert([
                'phase_id' => $fixture['phaseId'],
                'slug' => 'module-1',
                'title' => 'Duplicate Slug',
                'position' => 2,
            ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('modules')->insert([
                'phase_id' => $fixture['phaseId'],
                'slug' => 'module-zero',
                'title' => 'Module Zero',
                'position' => 0,
            ]))->toThrow(QueryException::class);
    });
});

describe('lessons schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('lessons');

        expect($columns->keys()->all())->toBe([
            'id', 'module_id', 'slug', 'title', 'position', 'content',
            'estimated_reading_minutes', 'created_at', 'updated_at',
        ])->and($columns['content']->is_nullable)->toBe('NO')
            ->and($columns['estimated_reading_minutes']->is_nullable)->toBe('YES');
    });

    it('rejects a lesson referencing a non-existent module', function () {
        expect(fn () => DB::table('lessons')->insert([
            'module_id' => (string) Str::uuid(),
            'slug' => 'orphan-lesson',
            'title' => 'Orphan Lesson',
            'position' => 1,
            'content' => 'Content.',
        ]))->toThrow(QueryException::class);
    });

    it('enforces unique position and slug within a module, and a positive position', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('lessons')->insert([
            'module_id' => $fixture['moduleId'],
            'slug' => 'lesson-1-duplicate-position',
            'title' => 'Duplicate Position',
            'position' => 1,
            'content' => 'Content.',
        ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('lessons')->insert([
                'module_id' => $fixture['moduleId'],
                'slug' => 'lesson-1',
                'title' => 'Duplicate Slug',
                'position' => 2,
                'content' => 'Content.',
            ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('lessons')->insert([
                'module_id' => $fixture['moduleId'],
                'slug' => 'lesson-zero',
                'title' => 'Lesson Zero',
                'position' => 0,
                'content' => 'Content.',
            ]))->toThrow(QueryException::class);
    });
});

describe('assignments schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('assignments');

        expect($columns->keys()->all())->toBe([
            'id', 'lesson_id', 'prompt', 'deliverable_name', 'minimum_word_count', 'created_at', 'updated_at',
        ])->and($columns['minimum_word_count']->is_nullable)->toBe('YES');
    });

    it('rejects an assignment referencing a non-existent lesson', function () {
        expect(fn () => DB::table('assignments')->insert([
            'lesson_id' => (string) Str::uuid(),
            'prompt' => 'Prompt.',
            'deliverable_name' => 'Deliverable',
        ]))->toThrow(QueryException::class);
    });

    it('allows at most one assignment per lesson', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('assignments')->insert([
            'lesson_id' => $fixture['lessonId'],
            'prompt' => 'Second prompt.',
            'deliverable_name' => 'Second Deliverable',
        ]))->toThrow(QueryException::class);
    });

    it('rejects a non-positive minimum word count but allows null', function () {
        $fixture = contentSchemaFixture();

        DB::table('assignments')->where('id', $fixture['assignmentId'])->delete();

        DB::table('assignments')->insert([
            'lesson_id' => $fixture['lessonId'],
            'prompt' => 'Prompt.',
            'deliverable_name' => 'Deliverable',
            'minimum_word_count' => null,
        ]);

        expect(DB::table('assignments')->where('lesson_id', $fixture['lessonId'])->exists())->toBeTrue();

        DB::table('assignments')->where('lesson_id', $fixture['lessonId'])->delete();

        // Postgres aborts the whole transaction on a failed statement, so the
        // constraint-violating insert must be the last query in this test.
        expect(fn () => DB::table('assignments')->insert([
            'lesson_id' => $fixture['lessonId'],
            'prompt' => 'Prompt.',
            'deliverable_name' => 'Deliverable',
            'minimum_word_count' => 0,
        ]))->toThrow(QueryException::class);
    });
});

describe('assignment_submissions schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('assignment_submissions');

        expect($columns->keys()->all())->toBe([
            'id', 'learner_id', 'assignment_id', 'content', 'status', 'submitted_at',
        ])->and($columns['status']->column_default)->toBe("'submitted'::text")
            ->and($columns['submitted_at']->column_default)->toBe('now()');
    });

    it('rejects a submission referencing a non-existent learner or assignment', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('assignment_submissions')->insert([
            'learner_id' => (string) Str::uuid(),
            'assignment_id' => $fixture['assignmentId'],
            'content' => 'Submission content.',
        ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('assignment_submissions')->insert([
                'learner_id' => $fixture['learnerId'],
                'assignment_id' => (string) Str::uuid(),
                'content' => 'Submission content.',
            ]))->toThrow(QueryException::class);
    });

    it('allows at most one submission per learner per assignment', function () {
        $fixture = contentSchemaFixture();

        DB::table('assignment_submissions')->insert([
            'learner_id' => $fixture['learnerId'],
            'assignment_id' => $fixture['assignmentId'],
            'content' => 'Submission content.',
        ]);

        expect(fn () => DB::table('assignment_submissions')->insert([
            'learner_id' => $fixture['learnerId'],
            'assignment_id' => $fixture['assignmentId'],
            'content' => 'Second submission.',
        ]))->toThrow(QueryException::class);
    });

    it('rejects a status outside the approved set', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('assignment_submissions')->insert([
            'learner_id' => $fixture['learnerId'],
            'assignment_id' => $fixture['assignmentId'],
            'content' => 'Submission content.',
            'status' => 'draft',
        ]))->toThrow(QueryException::class);
    });
});

describe('surveys schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('surveys');

        expect($columns->keys()->all())->toBe(['id', 'module_id', 'title', 'created_at', 'updated_at']);
    });

    it('rejects a survey referencing a non-existent module', function () {
        expect(fn () => DB::table('surveys')->insert([
            'module_id' => (string) Str::uuid(),
            'title' => 'Orphan Survey',
        ]))->toThrow(QueryException::class);
    });

    it('allows at most one survey per module', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('surveys')->insert([
            'module_id' => $fixture['moduleId'],
            'title' => 'Second Survey',
        ]))->toThrow(QueryException::class);
    });
});

describe('survey_questions schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('survey_questions');

        expect($columns->keys()->all())->toBe([
            'id', 'survey_id', 'position', 'question_text', 'question_type', 'required', 'created_at', 'updated_at',
        ])->and($columns['required']->data_type)->toBe('boolean')
            ->and($columns['required']->column_default)->toBe('true');
    });

    it('rejects a question referencing a non-existent survey', function () {
        expect(fn () => DB::table('survey_questions')->insert([
            'survey_id' => (string) Str::uuid(),
            'position' => 1,
            'question_text' => 'Orphan question?',
            'question_type' => 'rating',
        ]))->toThrow(QueryException::class);
    });

    it('enforces unique position within a survey and a positive position', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('survey_questions')->insert([
            'survey_id' => $fixture['surveyId'],
            'position' => 1,
            'question_text' => 'Duplicate position?',
            'question_type' => 'rating',
        ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('survey_questions')->insert([
                'survey_id' => $fixture['surveyId'],
                'position' => 0,
                'question_text' => 'Zero position?',
                'question_type' => 'rating',
            ]))->toThrow(QueryException::class);
    });

    it('rejects a question_type outside the approved set', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('survey_questions')->insert([
            'survey_id' => $fixture['surveyId'],
            'position' => 2,
            'question_text' => 'Invalid type?',
            'question_type' => 'boolean',
        ]))->toThrow(QueryException::class);
    });
});

describe('survey_responses schema', function () {
    it('matches the approved column set, types, and defaults', function () {
        $columns = contentSchemaColumns('survey_responses');

        expect($columns->keys()->all())->toBe([
            'id', 'learner_id', 'survey_question_id', 'answer_text', 'answer_rating', 'submitted_at',
        ])->and($columns['answer_text']->is_nullable)->toBe('YES')
            ->and($columns['answer_rating']->is_nullable)->toBe('YES');
    });

    it('rejects a response referencing a non-existent learner or survey question', function () {
        $fixture = contentSchemaFixture();

        expect(fn () => DB::table('survey_responses')->insert([
            'learner_id' => (string) Str::uuid(),
            'survey_question_id' => $fixture['surveyQuestionId'],
            'answer_rating' => 5,
        ]))->toThrow(QueryException::class)
            ->and(fn () => DB::table('survey_responses')->insert([
                'learner_id' => $fixture['learnerId'],
                'survey_question_id' => (string) Str::uuid(),
                'answer_rating' => 5,
            ]))->toThrow(QueryException::class);
    });

    it('allows at most one response per learner per question', function () {
        $fixture = contentSchemaFixture();

        DB::table('survey_responses')->insert([
            'learner_id' => $fixture['learnerId'],
            'survey_question_id' => $fixture['surveyQuestionId'],
            'answer_rating' => 4,
        ]);

        expect(fn () => DB::table('survey_responses')->insert([
            'learner_id' => $fixture['learnerId'],
            'survey_question_id' => $fixture['surveyQuestionId'],
            'answer_rating' => 5,
        ]))->toThrow(QueryException::class);
    });

    it('rejects a rating outside 1-5 but allows null', function () {
        $fixture = contentSchemaFixture();

        DB::table('survey_responses')->insert([
            'learner_id' => $fixture['learnerId'],
            'survey_question_id' => $fixture['surveyQuestionId'],
            'answer_text' => 'Free-text answer.',
            'answer_rating' => null,
        ]);

        expect(DB::table('survey_responses')->where('learner_id', $fixture['learnerId'])->exists())->toBeTrue();

        DB::table('survey_responses')->where('learner_id', $fixture['learnerId'])->delete();

        // Postgres aborts the whole transaction on a failed statement, so the
        // constraint-violating insert must be the last query in this test.
        expect(fn () => DB::table('survey_responses')->insert([
            'learner_id' => $fixture['learnerId'],
            'survey_question_id' => $fixture['surveyQuestionId'],
            'answer_rating' => 6,
        ]))->toThrow(QueryException::class);
    });
});
