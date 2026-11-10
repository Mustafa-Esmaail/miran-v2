<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
    }

    public function test_sitemap_lists_static_urls(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertSee('<loc>'.route('courses.index').'</loc>', false)
            ->assertSee('<loc>'.route('bundles.index').'</loc>', false);
    }

    public function test_sitemap_includes_active_pages_and_excludes_inactive_home_and_course_profile_pages(): void
    {
        $this->createPage('about-us', isActive: true);
        $this->createPage('landing-v2-page', isActive: true, template: 'landing-v2');
        $this->createPage('hidden-page', isActive: false);
        $this->createPage('home', isActive: true);
        $this->createPage('course-profile-page', isActive: true, template: 'course-profile');

        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertSee('<loc>'.route('pages.show', 'about-us').'</loc>', false);
        $response->assertSee('<loc>'.route('pages.show', 'landing-v2-page').'</loc>', false);
        $response->assertDontSee(route('pages.show', 'hidden-page'), false);
        $response->assertDontSee(route('pages.show', 'home'), false);
        $response->assertDontSee(route('pages.show', 'course-profile-page'), false);
    }

    public function test_sitemap_includes_only_published_courses(): void
    {
        $category = $this->createCategory();
        $this->createCourse($category, 'published-course', 'published');
        $this->createCourse($category, 'draft-course', 'draft');

        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertSee('<loc>'.route('courses.show', 'published-course').'</loc>', false);
        $response->assertDontSee(route('courses.show', 'draft-course'), false);
    }

    public function test_sitemap_includes_only_active_bundles(): void
    {
        $category = $this->createCategory();
        $this->createBundle($category, 'active-bundle', isActive: true);
        $this->createBundle($category, 'inactive-bundle', isActive: false);

        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertSee('<loc>'.route('bundles.select-groups', ['bundle' => 'active-bundle']).'</loc>', false);
        $response->assertDontSee(route('bundles.select-groups', ['bundle' => 'inactive-bundle']), false);
    }

    public function test_sitemap_excludes_blog_and_private_routes(): void
    {
        $response = $this->get(route('sitemap'))->assertOk();

        $response->assertDontSee(route('blog.index'), false);
        $response->assertDontSee('/dashboard', false);
        $response->assertDontSee('/admin', false);
        $response->assertDontSee(route('cart'), false);
        $response->assertDontSee(route('checkout'), false);
    }

    public function test_robots_txt_points_to_sitemap(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('Sitemap: '.route('sitemap'), false);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /dashboard', false);
    }

    private function createPage(string $slug, bool $isActive, ?string $template = null): Page
    {
        return Page::query()->create([
            'title' => ['en' => $slug],
            'slug' => $slug,
            'template' => $template,
            'is_active' => $isActive,
            'is_published' => true,
        ]);
    }

    private function createCategory(): Category
    {
        return Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'sitemap-category',
            'is_active' => true,
        ]);
    }

    private function createCourse(Category $category, string $slug, string $status): Course
    {
        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => $status,
        ]);
    }

    private function createBundle(Category $category, string $slug, bool $isActive): Bundle
    {
        return Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'price' => 1500,
            'is_active' => $isActive,
        ]);
    }
}
