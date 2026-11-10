<?php

namespace Database\Seeders;

use App\Enums\BlogCategoryStatus;
use App\Enums\BlogCommentStatus;
use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->first();
        if (! $author) {
            $author = User::factory()->create([
                'name' => 'Blog Author',
                'email' => 'blog.author@example.com',
            ]);
        }

        // Create / ensure categories exist
        $categories = collect([
            [
                'title' => ['en' => 'Announcements', 'ar' => 'إعلانات'],
                'slug' => 'announcements',
            ],
            [
                'title' => ['en' => 'Insights', 'ar' => 'رؤى'],
                'slug' => 'insights',
            ],
            [
                'title' => ['en' => 'Events', 'ar' => 'فعاليات'],
                'slug' => 'events',
            ],
        ])->map(function (array $data) {
            return BlogCategory::query()->firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'status' => BlogCategoryStatus::Active,
                ]
            );
        });

        $posts = collect();

        foreach ($categories as $category) {
            // Normalize $category->title to array (handles string/json/array)
            $catTitle = $category->title;

            if (is_string($catTitle)) {
                $decoded = json_decode($catTitle, true);
                $catTitle = is_array($decoded)
                    ? $decoded
                    : ['en' => $category->title, 'ar' => $category->title];
            }

            if (! is_array($catTitle)) {
                $catTitle = ['en' => (string) $category->title, 'ar' => (string) $category->title];
            }

            $catTitleEn = $catTitle['en'] ?? 'Category';
            $catTitleAr = $catTitle['ar'] ?? $catTitleEn;

            for ($i = 1; $i <= 4; $i++) {

                // Example rule: events drafts, others published
                $status = ($category->slug === 'events')
                    ? BlogStatus::Draft
                    : BlogStatus::Published;

                $publishedAt = ($status === BlogStatus::Published)
                    ? Carbon::now()->subDays(rand(1, 30))
                    : null;

                $titleEn = "{$catTitleEn} Post {$i}";
                $titleAr = "{$catTitleAr} {$i}";

                $contentEn = "This is {$catTitleEn} post {$i}.";
                $contentAr = "هذا هو منشور {$catTitleAr} رقم {$i}.";

                // Unique slug each time
                $slug = Str::slug($category->slug.'-post-'.$i.'-'.Str::random(6));

                $post = Blog::query()->create([
                    'slug' => $slug,
                    'title' => ['en' => $titleEn, 'ar' => $titleAr],
                    'content' => ['en' => $contentEn, 'ar' => $contentAr],
                    'short_description' => ['en' => $contentEn, 'ar' => $contentAr],
                    'long_description' => ['en' => $contentEn, 'ar' => $contentAr],
                    'author_id' => $author->id,
                    'category_id' => $category->id,
                    'status' => $status,
                    'published_at' => $publishedAt,
                ]);

                $posts->push($post);
            }
        }

        // Add one approved comment to the first published post
        $firstPublished = $posts->firstWhere('status', BlogStatus::Published);
        if ($firstPublished) {
            BlogComment::query()->firstOrCreate(
                [
                    'blog_id' => $firstPublished->id,
                    'user_id' => $author->id,
                    'status' => BlogCommentStatus::Approved,
                ],
                [
                    'content' => [
                        'en' => 'Great update, looking forward to more.',
                        'ar' => 'تحديث رائع، بانتظار المزيد.',
                    ],
                ]
            );
        }
    }
}
