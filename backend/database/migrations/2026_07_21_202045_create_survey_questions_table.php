<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('survey_id')->constrained('surveys');
            $table->integer('position');
            $table->text('question_text');
            $table->text('question_type');
            $table->boolean('required')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->unique(['survey_id', 'position']);
        });

        DB::statement('ALTER TABLE survey_questions ADD CONSTRAINT survey_questions_position_check CHECK (position > 0)');
        DB::statement("ALTER TABLE survey_questions ADD CONSTRAINT survey_questions_question_type_check CHECK (question_type IN ('rating', 'text'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
