<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('matches the approved PostgreSQL learner schema and defaults', function () {
    expect(DB::getDriverName())->toBe('pgsql')
        ->and(Schema::hasTable('learners'))->toBeTrue()
        ->and(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('password_reset_tokens'))->toBeFalse()
        ->and(Schema::hasTable('personal_access_tokens'))->toBeFalse();

    $columns = collect(DB::select(<<<'SQL'
        SELECT column_name, data_type, column_default, is_nullable
        FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'learners'
        ORDER BY ordinal_position
        SQL))->keyBy('column_name');

    expect($columns->keys()->all())->toBe([
        'id',
        'email',
        'display_name',
        'password_hash',
        'enrolled_at',
    ])->and($columns['id']->data_type)->toBe('uuid')
        ->and($columns['id']->column_default)->toBe('gen_random_uuid()')
        ->and($columns['email']->data_type)->toBe('text')
        ->and($columns['display_name']->data_type)->toBe('text')
        ->and($columns['password_hash']->data_type)->toBe('text')
        ->and($columns['enrolled_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['enrolled_at']->column_default)->toBe('now()')
        ->and($columns->pluck('is_nullable')->unique()->all())->toBe(['NO']);

    $primaryKeyColumns = DB::table('information_schema.table_constraints as constraints')
        ->join('information_schema.key_column_usage as columns', function ($join) {
            $join->on('constraints.constraint_name', '=', 'columns.constraint_name')
                ->on('constraints.table_schema', '=', 'columns.table_schema');
        })
        ->where('constraints.table_schema', DB::raw('current_schema()'))
        ->where('constraints.table_name', 'learners')
        ->where('constraints.constraint_type', 'PRIMARY KEY')
        ->orderBy('columns.ordinal_position')
        ->pluck('columns.column_name')
        ->all();

    expect($primaryKeyColumns)->toBe(['id']);

    $uniqueColumns = DB::table('information_schema.table_constraints as constraints')
        ->join('information_schema.key_column_usage as columns', function ($join) {
            $join->on('constraints.constraint_name', '=', 'columns.constraint_name')
                ->on('constraints.table_schema', '=', 'columns.table_schema');
        })
        ->where('constraints.table_schema', DB::raw('current_schema()'))
        ->where('constraints.table_name', 'learners')
        ->where('constraints.constraint_type', 'UNIQUE')
        ->orderBy('columns.ordinal_position')
        ->pluck('columns.column_name')
        ->all();

    expect($uniqueColumns)->toBe(['email']);

    DB::table('learners')->insert([
        'email' => 'schema@example.com',
        'display_name' => 'Schema Learner',
        'password_hash' => 'not-a-real-password-hash',
    ]);

    $learner = DB::table('learners')->where('email', 'schema@example.com')->first();

    expect($learner)->not->toBeNull()
        ->and(Str::isUuid($learner->id))->toBeTrue()
        ->and(Carbon::parse($learner->enrolled_at)->diffInSeconds(now()))->toBeLessThan(5);
});
