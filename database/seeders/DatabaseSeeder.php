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
    }
}
