<?php

namespace Database\Seeders;

use App\Models\Apitoken;
use App\Models\Credit;
use App\Models\MessageModel;
use App\Models\Subscription;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::create([
            'firstname' => 'admin',
            'lastname' => 'user',
            'email' => 'admin@email.com',
            'role' => 'admin',
            'password' => 'password',
        ]);

        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = User::create([
                'firstname' => fake()->firstName,
                'lastname' => fake()->lastName,
                'email' => fake()->email,
                'role' => 'user',
                'password' => 'password',
            ]);
        }

        $subscriptions = [];
        for ($i = 0; $i < 3; $i++) {
            $subscriptions[] = Subscription::create([
                'name' => 'Plan ' . (string) $i,
                'description' => fake()->paragraphs(rand(3, 6), true),
                'price' => 10 + (5 * $i),
                'characters' => 1000000 + ($i * 1000),
                'active' => true,
                'paypal_plan_id' => null
            ]);
        }

        $credits = [];
        foreach ($users as $user) {
            $amount = rand(5, 15); // 5~15 credits will be created
            for ($i = 0; $i < $amount; $i++) {
                $all_chars = rand(10, 100);

                $credits[] = Credit::create([
                    'user_id' => $user->id,
                    'subscription_id' => 1,
                    'characters' => $all_chars,
                    'characters_used' => $all_chars - rand(10, $all_chars),
                    'expires_at' => rand(0, 1) === 0 ? now()->subDays(rand(1, 10)) : now()->addDays(1, 10)
                ]);
            }
        }
    }
}
