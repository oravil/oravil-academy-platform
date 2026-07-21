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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('learner_id')->constrained('learners');
            $table->foreignUuid('survey_question_id')->constrained('survey_questions');
            $table->text('answer_text')->nullable();
            $table->integer('answer_rating')->nullable();
            $table->timestampTz('submitted_at')->default(DB::raw('now()'));

            $table->unique(['learner_id', 'survey_question_id']);
        });

        DB::statement('ALTER TABLE survey_responses ADD CONSTRAINT survey_responses_answer_rating_check CHECK (answer_rating IS NULL OR answer_rating BETWEEN 1 AND 5)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
