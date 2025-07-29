<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing users with profile data
        $users = User::all();
        
        foreach ($users as $user) {
            $user->update([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'bio' => fake()->sentence(),
                'country' => fake()->country(),
                'region' => fake()->state(),
                'postal_code' => fake()->postcode(),
            ]);
            
            // Generate user code if not exists
            if (!$user->user_code) {
                $user->user_code = User::generateUserCode();
                $user->save();
            }
        }
    }
}
