<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_product_url_renders_course_without_redirecting(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'product-redirect-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Laravel Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'laravel-course',
            'status' => 'published',
        ]);

        $response = $this->get('/product/'.$course->slug);

        $response->assertOk();
        $response->assertSee($course->getTranslation('title', 'en'));
    }

    public function test_legacy_product_url_renders_bundle_without_redirecting(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'product-redirect-bundle-category',
            'is_active' => true,
        ]);

        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Leadership Bundle'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'leadership-bundle',
            'price' => 1500,
            'is_active' => true,
        ]);

        $response = $this->get('/product/'.$bundle->slug);

        $response->assertOk();
    }

    public function test_legacy_product_url_404s_for_unknown_slug(): void
    {
        $response = $this->get('/product/does-not-exist');

        $response->assertNotFound();
    }
}
