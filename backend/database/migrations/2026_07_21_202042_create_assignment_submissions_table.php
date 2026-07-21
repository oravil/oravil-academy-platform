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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('learner_id')->constrained('learners');
            $table->foreignUuid('assignment_id')->constrained('assignments');
            $table->text('content');
            $table->text('status')->default('submitted');
            $table->timestampTz('submitted_at')->default(DB::raw('now()'));

            $table->unique(['learner_id', 'assignment_id']);
        });

        DB::statement("ALTER TABLE assignment_submissions ADD CONSTRAINT assignment_submissions_status_check CHECK (status IN ('submitted'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
