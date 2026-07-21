<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Real MVP content (Phase 0, Module 1) — see SPRINT-001, OA-MVP-004, OA-MVP-006.
        $this->call(ContentSeeder::class);

        // Dev convenience only — a local test learner, not MVP content.
        $this->call(DevLearnerSeeder::class);
    }
}
