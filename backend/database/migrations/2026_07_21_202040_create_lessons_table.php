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
        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('module_id')->constrained('modules');
            $table->text('slug');
            $table->text('title');
            $table->integer('position');
            $table->text('content');
            $table->integer('estimated_reading_minutes')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('now()'));
            $table->timestampTz('updated_at')->default(DB::raw('now()'));

            $table->unique(['module_id', 'position']);
            $table->unique(['module_id', 'slug']);
        });

        DB::statement('ALTER TABLE lessons ADD CONSTRAINT lessons_position_check CHECK (position > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
