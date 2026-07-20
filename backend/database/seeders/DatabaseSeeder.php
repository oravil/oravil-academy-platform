<?php

namespace Database\Seeders;

use App\Models\Learner;
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
        // Learner::factory(10)->create();

        Learner::factory()->create([
            'display_name' => 'Test Learner',
            'email' => 'test@example.com',
            'enrolled_at' => now(),
        ]);
    }
}
