<?php

namespace Database\Seeders;

use App\Models\Apitoken;
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

        // $credits = [];
        // foreach ($users as $user) {
        //     for ($i = 0; $i < 10; $i++) {
        //         $credits[] = $user->credits()->create([
        //             'credits' => rand(100000, 1000000),
        //             'expires_at' => rand(0, 1) > 0 ?
        //                 \Carbon\Carbon::now()->subDays(rand(10, 100))
        //                 : \Carbon\Carbon::now()->addDays(rand(10, 100))
        //         ]);
        //     }
        // }

        // $chats = [];
        // $message_models = [];
        // foreach ($users as $user) {
        //     for ($i = 0; $i < 9; $i++) {
        //         $chats[] = $user->chats()->create([
        //             'topic' => fake()->sentence()
        //         ]);
        //     }

        //     for ($i = 0; $i < 3; $i++) {
        //         $vars = json_encode([
        //             'var1' => fake()->sentence(),
        //             'var2' => fake()->sentence(),
        //             'var3' => fake()->sentence(),
        //         ]);
        //         $model = fake()->sentence() . " {var1} " . fake()->sentence() . " {var2} " . fake()->sentence() . " {var3} " . fake()->sentence();

        //         $message_models[] = $user->messageModels()->create([
        //             'variables' => $vars,
        //             'model' => $model
        //         ]);
        //     }
        // }

        // $messages = [];
        // foreach($chats as $chat){
        //     for($i = 0; $i < rand(5, 20); $i++)
        //     $messages[] = $chat->messages()->create([
        //         'sender' => ($i % 2 === 0) ? 'user' : 'assistant',
        //         'message' => fake()->paragraphs(rand(1, 5), true),
        //         'order' => $i
        //     ]);
        // }
    }
}
