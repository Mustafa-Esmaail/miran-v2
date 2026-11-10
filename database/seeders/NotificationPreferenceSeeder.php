<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();
        foreach ($users as $user) {
            NotificationPreference::query()->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'email_enabled' => true,
                'database_enabled' => true,
                'announcements_enabled' => true,
                'course_updates_enabled' => true,
                'comment_replies_enabled' => true,
            ]);
        }
    }
}
