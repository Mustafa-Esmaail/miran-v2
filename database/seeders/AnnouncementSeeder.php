<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $announcements = [
            [
                'title' => ['en' => 'Welcome to Miran Learning Center', 'ar' => 'مرحباً بكم في مركز مران القادة للتدريب'],
                'body' => ['en' => 'Check the latest course updates and announcements.', 'ar' => 'تابع أحدث تحديثات الدورات والإعلانات.'],
                'status' => 'published',
                'starts_at' => now()->subDay(),
                'ends_at' => null,
            ],
            [
                'title' => ['en' => 'New Courses Available', 'ar' => 'دورات جديدة متاحة'],
                'body' => ['en' => 'Explore newly added courses in your dashboard.', 'ar' => 'استكشف الدورات الجديدة في لوحة التحكم.'],
                'status' => 'published',
                'starts_at' => now()->subDays(2),
                'ends_at' => null,
            ],
            [
                'title' => ['en' => 'Maintenance Window', 'ar' => 'نافذة صيانة'],
                'body' => ['en' => 'Scheduled maintenance this weekend.', 'ar' => 'صيانة مجدولة في نهاية الأسبوع.'],
                'status' => 'draft',
                'starts_at' => null,
                'ends_at' => null,
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::query()->create($announcement);
        }
    }
}