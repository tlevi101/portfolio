<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Note: avoid factories/Faker here — this seeder runs in production, where
     * Faker is not installed (composer install --no-dev).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tensura101.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')],
        );

        $this->call(PortfolioSeeder::class);

        // Gives a freshly seeded CV the identity, skills and projects it used to
        // read off its portfolio. Only fills what is empty, so it is a no-op on
        // an install where the CVs already carry their own content.
        $this->call(CvContentFromPortfolioSeeder::class);
    }
}
