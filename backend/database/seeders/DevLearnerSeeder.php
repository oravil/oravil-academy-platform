<?php

namespace Database\Seeders;

use App\Models\Learner;
use Illuminate\Database\Seeder;

/**
 * Dev convenience only — a local test learner for manual login testing.
 * Not MVP content; see ContentSeeder for that.
 */
class DevLearnerSeeder extends Seeder
{
    public function run(): void
    {
        Learner::firstOrCreate(
            ['email' => 'test@example.com'],
            Learner::factory()->raw([
                'email' => 'test@example.com',
                'display_name' => 'Test Learner',
                'enrolled_at' => now(),
            ])
        );
    }
}
