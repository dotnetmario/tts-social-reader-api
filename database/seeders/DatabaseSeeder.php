<?php

namespace Database\Seeders;

use App\Models\Apitoken;
use App\Models\MessageModel;
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
        $apitoken = Apitoken::create([
            'model' => 'gpt-4o',
            'expires_at' => \Carbon\Carbon::now()->addMonth()->format('Y-m-d H:i:s'),
            'tokens' => rand(10000, 90000)
        ]);

        $admin = User::create([
            'firstname' => 'admin',
            'lastname' => 'user',
            'email' => 'admin@email.com',
            'role' => 'admin',
            'password' => 'password',
        ]);

        $users = [];
        for ($i = 0; $i < 9; $i++) {
            $users[] = User::create([
                'firstname' => fake()->firstName,
                'lastname' => fake()->lastName,
                'email' => fake()->email,
                'role' => 'user',
                'password' => 'password',
            ]);
        }

        $chats = [];
        $message_models = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 9; $i++) {
                $chats[] = $user->chats()->create([
                    'topic' => fake()->sentence()
                ]);
            }

            for ($i = 0; $i < 3; $i++) {
                $vars = json_encode([
                    'var1' => fake()->sentence(),
                    'var2' => fake()->sentence(),
                    'var3' => fake()->sentence(),
                ]);
                $model = fake()->sentence() . " {var1} " . fake()->sentence() . " {var2} " . fake()->sentence() . " {var3} " . fake()->sentence();

                $message_models[] = $user->messageModels()->create([
                    'variables' => $vars,
                    'model' => $model
                ]);
            }
        }

        $messages = [];
        foreach($chats as $chat){
            for($i = 0; $i < rand(5, 20); $i++)
            $messages[] = $chat->messages()->create([
                'sender' => ($i % 2 === 0) ? 'user' : 'assistant',
                'message' => fake()->paragraphs(rand(1, 5), true),
                'order' => $i
            ]);
        }
    }
}
