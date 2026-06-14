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
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(SuperAdminSeeder::class);
        $this->call(ManagerSeeder::class);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'last_name' => 'Seed',
                'username' => 'test-user',
                'role_id' => null,
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );
    }
}
