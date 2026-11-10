<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Discount;
use App\Models\Lesson;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Section;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class MiranCourseImporter
{
    private const LISTING_URL = 'https://backup.themiran.net/courses/';

    private const PRODUCT_PATH = '/product/';

    private const REQUEST_ATTEMPTS = 3;

    private Client $client;

    private MiranRichContentCleaner $cleaner;

    public function __construct(?Client $client = null, ?MiranRichContentCleaner $cleaner = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://backup.themiran.net',
            'timeout' => 15,
            'connect_timeout' => 7,
            'headers' => [
                'User-Agent' => 'MiranLmsBot/1.0',
                'Accept-Language' => 'ar,en;q=0.8',
            ],
        ]);
        $this->cleaner = $cleaner ?? new MiranRichContentCleaner;
    }

    public function import(?callable $progress = null, ?int $limit = null, bool $syncCms = true): array
    {
        $listingItems = $this->fetchListingItems();
        if ($limit !== null && $limit > 0) {
            $listingItems = array_slice($listingItems, 0, $limit);
        }

        $results = [
            'total' => count($listingItems),
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ];

        foreach ($listingItems as $item) {
            $sourceUrl = $item['source_url'];

            try {
                $details = $this->fetchProductDetails($sourceUrl);
                $payload = $this->mergeListingAndProduct($item, $details);

                DB::transaction(function () use ($payload, &$results) {
                    $category = $this->upsertCategory($payload['category_ar'] ?? null);
                    $course = $this->upsertCourse($category->id, $payload);
                    $this->ensureCourseDefaults($course, $payload);

                    if ($course->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                });

                if ($progress) {
                    $progress('imported', $sourceUrl);
                }
            } catch (\Throwable $e) {
                $results['failed']++;

                Log::warning('Miran course import failed.', [
                    'source_url' => $sourceUrl,
                    'error' => $e->getMessage(),
                ]);

                if ($progress) {
                    $progress('failed', $sourceUrl);
                }
            }
        }

        if ($syncCms) {
            $this->syncCmsFromMiranCourses();
        }

        return $results;
    }

    public function importFromCsv(string $filePath, ?callable $progress = null): array
    {
        if (! is_file($filePath)) {
            throw new \InvalidArgumentException('CSV file not found: '.$filePath);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV file: '.$filePath);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty: '.$filePath);
        }

        $normalizedHeader = array_map(fn ($value) => $this->normalizeCsvHeader((string) $value), $header);

        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
        ];

        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isCsvRowEmpty($row)) {
                continue;
            }

            $results['total']++;

            try {
                $payload = $this->buildPayloadFromCsvRow($normalizedHeader, $row);

                DB::transaction(function () use ($payload, &$results) {
                    $category = $this->upsertCategory($payload['category_ar'] ?? null);
                    $course = $this->upsertCourse($category->id, $payload);
                    $this->ensureCourseDefaults($course, $payload);

                    if ($course->wasRecentlyCreated) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                });

                if ($progress) {
                    $progress('imported', (string) ($payload['title_ar'] ?? ('row:'.$line)));
                }
            } catch (\Throwable $e) {
                $results['failed']++;

                Log::warning('Miran CSV course import failed.', [
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);

                if ($progress) {
                    $progress('failed', 'line '.$line);
                }
            }
        }

        fclose($handle);

        return $results;
    }

    private function syncCmsFromMiranCourses(): void
    {
        try {
            $courses = Course::query()
                ->published()
                ->with('category')
                ->orderByDesc('created_at')
                ->get();

            if ($courses->isEmpty()) {
                return;
            }

            $categories = Category::query()
                ->withCount('courses')
                ->orderByDesc('courses_count')
                ->get();

            $pool = $courses->values();
            $homeCourse = $pool->get(0);
            $aboutCourse = $pool->get(1) ?? $homeCourse;
            $contactCourse = $pool->get(2) ?? $homeCourse;
            $complaintsCourse = $pool->get(3) ?? $homeCourse;
            $corporateCourse = $pool->get(4) ?? $homeCourse;

            $home = $this->upsertPage(
                'home',
                $this->pageTitleFromCourse($homeCourse, 'Home'),
                $this->pageBodyFromCourse($homeCourse),
                'home'
            );
            $about = $this->upsertPage(
                'about',
                $this->pageTitleFromCourse($aboutCourse, 'About'),
                $this->pageBodyFromCourse($aboutCourse),
                null
            );
            $contact = $this->upsertPage(
                'contact',
                $this->pageTitleFromCourse($contactCourse, 'Contact'),
                $this->pageBodyFromCourse($contactCourse),
                null
            );
            $complaints = $this->upsertPage(
                'complaints',
                $this->pageTitleFromCourse($complaintsCourse, 'Complaints'),
                $this->pageBodyFromCourse($complaintsCourse),
                null
            );
            $corporate = $this->upsertPage(
                'corporate',
                $this->pageTitleFromCourse($corporateCourse, 'Corporate Training'),
                $this->pageBodyFromCourse($corporateCourse),
                null
            );

            $this->syncSections($home, $this->homeSections($homeCourse, $complaintsCourse, $courses, $categories));
            $this->syncSections($about, $this->aboutSections($aboutCourse, $courses, $categories));
            $this->syncSections($contact, $this->contactSections($contactCourse, $categories));
            $this->syncSections($complaints, $this->complaintsSections($complaintsCourse));
            $this->syncSections($corporate, $this->corporateSections($corporateCourse, $courses, $categories));

            $this->syncMenu($home, $about, $contact, $corporate, $complaints, $categories->first());
        } catch (\Throwable $e) {
            Log::warning('CMS sync from Miran courses failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function upsertPage(string $slug, array $title, array $body, ?string $template): Page
    {
        return Page::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $title,
                'body' => $body,
                'template' => $template,
                'is_active' => true,
                'is_published' => true,
            ]
        );
    }

    private function syncSections(Page $page, array $sections): void
    {
        Section::where('page_id', $page->id)->delete();

        foreach ($sections as $section) {
            Section::create([
                'page_id' => $page->id,
                'type' => $section['type'],
                'title' => $section['title'],
                'subtitle' => $section['subtitle'],
                'content' => $section['content'],
                'is_active' => true,
                'sort_order' => $section['sort_order'],
            ]);
        }
    }

    private function syncMenu(
        Page $home,
        Page $about,
        Page $contact,
        Page $corporate,
        Page $complaints,
        ?Category $primaryCategory
    ): void {
        $menu = Menu::firstOrCreate(
            ['location' => 'header'],
            ['name' => 'Header', 'is_active' => true]
        );

        if ($menu->items()->exists()) {
            return;
        }

        $coursesLabel = $primaryCategory
            ? $this->makeTranslatableFromCategory($primaryCategory)
            : ['en' => 'Courses', 'ar' => 'Courses'];

        $items = [
            ['label' => $home->title, 'type' => 'page', 'page_id' => $home->id, 'url' => null, 'sort_order' => 1],
            ['label' => $about->title, 'type' => 'page', 'page_id' => $about->id, 'url' => null, 'sort_order' => 2],
            ['label' => $coursesLabel, 'type' => 'custom', 'page_id' => null, 'url' => '/courses', 'sort_order' => 3],
            ['label' => $contact->title, 'type' => 'page', 'page_id' => $contact->id, 'url' => null, 'sort_order' => 4],
            ['label' => $corporate->title, 'type' => 'page', 'page_id' => $corporate->id, 'url' => null, 'sort_order' => 5],
            ['label' => $complaints->title, 'type' => 'page', 'page_id' => $complaints->id, 'url' => null, 'sort_order' => 6],
        ];

        foreach ($items as $item) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'label' => $item['label'],
                'type' => $item['type'],
                'page_id' => $item['page_id'],
                'url' => $item['url'],
                'parent_id' => null,
                'open_in_new_tab' => false,
                'sort_order' => $item['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    private function homeSections(
        ?Course $homeCourse,
        ?Course $complaintsCourse,
        \Illuminate\Support\Collection $courses,
        \Illuminate\Support\Collection $categories
    ): array {
        $secondaryCourse = $courses->get(1) ?? $homeCourse;
        $tertiaryCourse = $courses->get(2) ?? $homeCourse;
        $ctaCourse = $courses->get(3) ?? $homeCourse;
        $corporateCourse = $courses->get(4) ?? $homeCourse;

        return [
            $this->buildHeroSection($homeCourse, 'default', 10),
            $this->buildCtaBannerSection($ctaCourse, 20),
            $this->buildCategoriesSection($secondaryCourse, 30),
            $this->buildFeaturedCoursesSection($secondaryCourse, 40),
            $this->buildSpecialBundlesSection($tertiaryCourse, 50),
            $this->buildPartnersSection($courses, 60),
            $this->buildWhySection($categories, $courses, 70),
            $this->buildCorporateSection($corporateCourse, 80),
            $this->buildTestimonialsSection($courses, 90),
            $this->buildCtaSection($ctaCourse, 100),
            $this->buildStatsSection($categories, 110),
            $this->buildLeadFormSection($homeCourse, 'default', 120),
            $this->buildComplaintsSection($complaintsCourse ?? $homeCourse, 130),
        ];
    }

    private function aboutSections(
        ?Course $aboutCourse,
        \Illuminate\Support\Collection $courses,
        \Illuminate\Support\Collection $categories
    ): array {
        return [
            $this->buildHeroSection($aboutCourse, 'about', 10),
            $this->buildValuesSection($aboutCourse, $courses, 20),
            $this->buildStorySection($courses, 30),
            $this->buildPartnersSection($courses, 40),
            $this->buildWhySection($categories, $courses, 50),
            $this->buildCtaSection($aboutCourse, 60),
        ];
    }

    private function contactSections(?Course $contactCourse, \Illuminate\Support\Collection $categories): array
    {
        return [
            $this->buildContactHeroSection($contactCourse, $categories, 10),
            $this->buildLeadFormSection($contactCourse, 'contact', 20),
        ];
    }

    private function complaintsSections(?Course $complaintsCourse): array
    {
        return [
            $this->buildHeroSection($complaintsCourse, 'complaint', 10),
            $this->buildLeadFormSection($complaintsCourse, 'complaint', 20),
        ];
    }

    private function corporateSections(
        ?Course $corporateCourse,
        \Illuminate\Support\Collection $courses,
        \Illuminate\Support\Collection $categories
    ): array {
        return [
            $this->buildHeroSection($corporateCourse, 'default', 10),
            $this->buildFeaturesSection($courses, 20),
            $this->buildFaqSection($courses, 30),
            $this->buildStatsSection($categories, 40),
            $this->buildCtaSection($corporateCourse, 50),
        ];
    }

    private function buildHeroSection(?Course $course, string $layout, int $sortOrder): array
    {
        [$headingAr, $headingEn] = $this->courseTextPair($course, 'title', 120);
        [$subheadingAr, $subheadingEn] = $this->courseTextPair($course, 'summary', 240);
        [$tagAr, $tagEn] = $this->courseCategoryPair($course);

        return $this->buildSection(
            'hero',
            null,
            null,
            [
                'layout' => $layout,
                'tag_ar' => $tagAr,
                'tag_en' => $tagEn,
                'heading_ar' => $headingAr,
                'heading_en' => $headingEn,
                'subheading_ar' => $subheadingAr,
                'subheading_en' => $subheadingEn,
                'foreground_image' => $this->courseImage($course),
                'image' => $this->courseImage($course),
                'stats' => $this->buildStatsItemsFromCategories($this->getTopCategories(3)),
            ],
            $sortOrder
        );
    }

    private function buildCtaBannerSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 240);

        return $this->buildSection(
            'cta_banner',
            $titleEn,
            $subtitleEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
                'button_text_ar' => $titleAr,
                'button_text_en' => $titleEn,
                'button_url' => $this->courseUrl($course),
                'background_image' => $this->courseImage($course),
            ],
            $sortOrder
        );
    }

    private function buildCategoriesSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseCategoryPair($course);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 220);

        return $this->buildSection(
            'categories',
            $titleEn,
            $subtitleEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
            ],
            $sortOrder
        );
    }

    private function buildFeaturedCoursesSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 220);

        return $this->buildSection(
            'featured_courses',
            $titleEn,
            $subtitleEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
                'button_text_ar' => $titleAr,
                'button_text_en' => $titleEn,
                'button_url' => '/courses',
            ],
            $sortOrder
        );
    }

    private function buildSpecialBundlesSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 220);

        return $this->buildSection(
            'special_bundles',
            $titleEn,
            $subtitleEn,
            [
                'button_text_ar' => $titleAr,
                'button_text_en' => $titleEn,
                'button_url' => '/bundles',
            ],
            $sortOrder
        );
    }

    private function buildPartnersSection(\Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        $logos = $courses
            ->pluck('image_url')
            ->filter()
            ->unique()
            ->take(6)
            ->values()
            ->all();

        return $this->buildSection(
            'partners',
            null,
            null,
            [
                'layout' => 'grid',
                'logos' => $logos,
            ],
            $sortOrder
        );
    }

    private function buildWhySection(
        \Illuminate\Support\Collection $categories,
        \Illuminate\Support\Collection $courses,
        int $sortOrder
    ): array {
        $points = [];

        foreach ($categories->take(4) as $category) {
            $course = $courses->firstWhere('category_id', $category->id);
            [$bodyAr, $bodyEn] = $this->courseTextPair($course, 'summary', 160);
            [$titleAr, $titleEn] = $this->categoryNamePair($category);

            $points[] = [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'body_ar' => $bodyAr,
                'body_en' => $bodyEn,
            ];
        }

        return $this->buildSection(
            'why',
            null,
            null,
            ['points' => $points],
            $sortOrder
        );
    }

    private function buildCorporateSection(?Course $course, int $sortOrder): array
    {
        [$tagAr, $tagEn] = $this->courseCategoryPair($course);
        [$buttonAr, $buttonEn] = $this->courseTextPair($course, 'title', 80);

        return $this->buildSection(
            'corporate',
            null,
            null,
            [
                'tag_ar' => $tagAr,
                'tag_en' => $tagEn,
                'button_text_ar' => $buttonAr,
                'button_text_en' => $buttonEn,
                'button_url' => $this->courseUrl($course),
            ],
            $sortOrder
        );
    }

    private function buildTestimonialsSection(\Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        $items = [];
        foreach ($courses->take(4) as $course) {
            [$textAr, $textEn] = $this->courseTextPair($course, 'summary', 180);
            [$nameAr, $nameEn] = $this->courseTextPair($course, 'title', 80);
            [$roleAr, $roleEn] = $this->courseCategoryPair($course);

            $items[] = [
                'text_ar' => $textAr,
                'text_en' => $textEn,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'role_ar' => $roleAr,
                'role_en' => $roleEn,
                'avatar' => $this->courseImage($course),
            ];
        }

        return $this->buildSection(
            'testimonials',
            null,
            null,
            ['items' => $items],
            $sortOrder
        );
    }

    private function buildCtaSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$textAr, $textEn] = $this->courseTextPair($course, 'summary', 220);

        return $this->buildSection(
            'cta',
            $titleEn,
            $textEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'text_ar' => $textAr,
                'text_en' => $textEn,
                'button_text_ar' => $titleAr,
                'button_text_en' => $titleEn,
                'button_url' => $this->courseUrl($course),
                'image' => $this->courseImage($course),
                'logos' => [],
            ],
            $sortOrder
        );
    }

    private function buildStatsSection(\Illuminate\Support\Collection $categories, int $sortOrder): array
    {
        return $this->buildSection(
            'stats',
            null,
            null,
            ['items' => $this->buildStatsItemsFromCategories($categories->take(3))],
            $sortOrder
        );
    }

    private function buildLeadFormSection(?Course $course, string $layout, int $sortOrder): array
    {
        [$headingAr, $headingEn] = $this->courseTextPair($course, 'title', 140);
        [$descriptionAr, $descriptionEn] = $this->courseTextPair($course, 'summary', 240);

        return $this->buildSection(
            'lead_form',
            $headingEn,
            $descriptionEn,
            [
                'layout' => $layout,
                'heading_ar' => $headingAr,
                'heading_en' => $headingEn,
                'description_ar' => $descriptionAr,
                'description_en' => $descriptionEn,
                'submit_text_ar' => $headingAr,
                'submit_text_en' => $headingEn,
                'success_message_ar' => $headingAr,
                'success_message_en' => $headingEn,
                'image' => $this->courseImage($course),
            ],
            $sortOrder
        );
    }

    private function buildComplaintsSection(?Course $course, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 200);

        return $this->buildSection(
            'complaints',
            $titleEn,
            $subtitleEn,
            [
                'tag_ar' => $titleAr,
                'tag_en' => $titleEn,
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
                'button_text_ar' => $titleAr,
                'button_text_en' => $titleEn,
                'button_url' => $this->courseUrl($course),
            ],
            $sortOrder
        );
    }

    private function buildContactHeroSection(?Course $course, \Illuminate\Support\Collection $categories, int $sortOrder): array
    {
        [$headingAr, $headingEn] = $this->courseTextPair($course, 'title', 140);
        [$subheadingAr, $subheadingEn] = $this->courseTextPair($course, 'summary', 220);

        $icons = ['pin', 'clock', 'chat', 'phone'];
        $cards = [];
        foreach ($categories->take(4) as $index => $category) {
            [$titleAr, $titleEn] = $this->categoryNamePair($category);
            $cards[] = [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'body_ar' => $titleAr,
                'body_en' => $titleEn,
                'icon' => $icons[$index] ?? 'pin',
            ];
        }

        return $this->buildSection(
            'contact_hero',
            $headingEn,
            $subheadingEn,
            [
                'heading_ar' => $headingAr,
                'heading_en' => $headingEn,
                'subheading_ar' => $subheadingAr,
                'subheading_en' => $subheadingEn,
                'cards' => $cards,
            ],
            $sortOrder
        );
    }

    private function buildValuesSection(?Course $course, \Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($course, 'summary', 220);
        $cards = [];
        foreach ($courses->take(3) as $item) {
            [$cardTitleAr, $cardTitleEn] = $this->courseTextPair($item, 'title', 100);
            [$bodyAr, $bodyEn] = $this->courseTextPair($item, 'summary', 160);
            $cards[] = [
                'title_ar' => $cardTitleAr,
                'title_en' => $cardTitleEn,
                'body_ar' => $bodyAr,
                'body_en' => $bodyEn,
            ];
        }

        return $this->buildSection(
            'values',
            $titleEn,
            $subtitleEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
                'cards' => $cards,
            ],
            $sortOrder
        );
    }

    private function buildStorySection(\Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        $primary = $courses->get(0);
        [$titleAr, $titleEn] = $this->courseTextPair($primary, 'title', 140);
        [$subtitleAr, $subtitleEn] = $this->courseTextPair($primary, 'summary', 200);

        $blocks = [];
        foreach ($courses->take(2) as $index => $course) {
            [$bodyAr, $bodyEn] = $this->courseTextPair($course, 'description', 240);
            $blocks[] = [
                'body_ar' => $bodyAr,
                'body_en' => $bodyEn,
                'image' => $this->courseImage($course),
                'alignment' => $index % 2 === 0 ? 'image-left' : 'image-right',
            ];
        }

        return $this->buildSection(
            'story',
            $titleEn,
            $subtitleEn,
            [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'subtitle_ar' => $subtitleAr,
                'subtitle_en' => $subtitleEn,
                'blocks' => $blocks,
            ],
            $sortOrder
        );
    }

    private function buildFeaturesSection(\Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        $items = [];
        foreach ($courses->take(4) as $course) {
            [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 100);
            [$descAr, $descEn] = $this->courseTextPair($course, 'summary', 160);
            $items[] = [
                'title_ar' => $titleAr,
                'title_en' => $titleEn,
                'description_ar' => $descAr,
                'description_en' => $descEn,
                'image' => $this->courseImage($course),
                'icon' => 'star',
            ];
        }

        return $this->buildSection(
            'features',
            null,
            null,
            ['items' => $items],
            $sortOrder
        );
    }

    private function buildFaqSection(\Illuminate\Support\Collection $courses, int $sortOrder): array
    {
        $items = [];
        foreach ($courses->take(4) as $course) {
            [$questionAr, $questionEn] = $this->courseTextPair($course, 'title', 120);
            [$answerAr, $answerEn] = $this->courseTextPair($course, 'summary', 200);
            $items[] = [
                'question_ar' => $questionAr,
                'question_en' => $questionEn,
                'answer_ar' => $answerAr,
                'answer_en' => $answerEn,
            ];
        }

        return $this->buildSection(
            'faq',
            null,
            null,
            ['items' => $items],
            $sortOrder
        );
    }

    private function buildSection(
        string $type,
        ?string $title,
        ?string $subtitle,
        array $content,
        int $sortOrder
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'content' => $this->filterContent($content),
            'sort_order' => $sortOrder,
        ];
    }

    private function pageTitleFromCourse(?Course $course, string $fallbackEn): array
    {
        [$titleAr, $titleEn] = $this->courseTextPair($course, 'title', 120);

        return [
            'ar' => $titleAr ?: $fallbackEn,
            'en' => $titleEn ?: $fallbackEn,
        ];
    }

    private function pageBodyFromCourse(?Course $course): array
    {
        [$bodyAr, $bodyEn] = $this->courseTextPair($course, 'description', 600);

        $bodyAr = $bodyAr ? '<p>'.$bodyAr.'</p>' : '';
        $bodyEn = $bodyEn ? '<p>'.$bodyEn.'</p>' : '';

        return [
            'ar' => $bodyAr,
            'en' => $bodyEn,
        ];
    }

    private function courseTextPair(?Course $course, string $field, int $limit = 0): array
    {
        $ar = $this->normalizeText($course?->getTranslation($field, 'ar', false));
        $en = $this->normalizeText($course?->getTranslation($field, 'en', false));

        if (! $en && $ar) {
            $en = $this->translateToEnglish($ar);
        }

        if ($limit > 0) {
            $ar = $ar ? Str::limit($ar, $limit, '...') : null;
            $en = $en ? Str::limit($en, $limit, '...') : null;
        }

        return [$ar, $en];
    }

    private function courseCategoryPair(?Course $course): array
    {
        $category = $course?->category;
        if (! $category) {
            return [null, null];
        }

        return $this->categoryNamePair($category);
    }

    private function categoryNamePair(Category $category): array
    {
        $ar = $this->normalizeText($category->getTranslation('name', 'ar', false));
        $en = $this->normalizeText($category->getTranslation('name', 'en', false));

        if (! $en && $ar) {
            $en = $this->translateToEnglish($ar);
        }

        return [$ar, $en];
    }

    private function makeTranslatableFromCategory(Category $category): array
    {
        [$ar, $en] = $this->categoryNamePair($category);

        return [
            'ar' => $ar ?: 'Courses',
            'en' => $en ?: 'Courses',
        ];
    }

    private function courseImage(?Course $course): ?string
    {
        return $course?->image_url;
    }

    private function courseUrl(?Course $course): string
    {
        if (! $course?->slug) {
            return '/courses';
        }

        return '/courses/'.$course->slug;
    }

    private function normalizeText(?string $text): ?string
    {
        if (! $text) {
            return null;
        }

        $clean = trim(strip_tags($text));
        $clean = preg_replace('/\s+/u', ' ', $clean);

        return $clean !== '' ? $clean : null;
    }

    private function filterContent(array $content): array
    {
        $filtered = [];
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $filtered[$key] = $this->filterContent($value);

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    private function buildStatsItemsFromCategories(\Illuminate\Support\Collection $categories): array
    {
        $items = [];
        foreach ($categories as $category) {
            [$labelAr, $labelEn] = $this->categoryNamePair($category);
            $items[] = [
                'value' => (string) ($category->courses_count ?? 0),
                'label_ar' => $labelAr,
                'label_en' => $labelEn,
            ];
        }

        return $items;
    }

    private function getTopCategories(int $limit): \Illuminate\Support\Collection
    {
        return Category::query()
            ->withCount('courses')
            ->orderByDesc('courses_count')
            ->limit($limit)
            ->get();
    }

    private function fetchListingItems(): array
    {
        $html = $this->requestHtml(self::LISTING_URL);
        $crawler = new Crawler($html);

        $items = [];
        $currentCategory = null;

        $crawler
            ->filterXPath('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::a]')
            ->each(function (Crawler $node) use (&$items, &$currentCategory) {
                $nodeName = strtolower($node->getNode(0)?->nodeName ?? '');

                if (in_array($nodeName, ['h1', 'h2', 'h3', 'h4'], true)) {
                    $text = trim($node->text());
                    if ($this->looksLikeCategory($text)) {
                        $currentCategory = $text;
                    }

                    return;
                }

                if ($nodeName !== 'a') {
                    return;
                }

                $href = $node->attr('href');
                if (! $this->isProductUrl($href)) {
                    return;
                }

                $sourceUrl = $this->normalizeUrl($href);
                if (isset($items[$sourceUrl])) {
                    return;
                }

                $title = trim($node->text());
                $container = $this->findProductContainer($node);
                $containerText = $container?->text() ?? '';

                [$price, $oldPrice] = $this->extractPricesFromText($containerText);
                $startDate = $this->extractStartDate($containerText);
                $category = $this->extractCategoryFromContainer($container) ?? $currentCategory;

                $items[$sourceUrl] = [
                    'source_url' => $sourceUrl,
                    'title_ar' => $title !== '' ? $title : null,
                    'category_ar' => $category,
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'start_date' => $startDate,
                ];
            });

        return array_values($items);
    }

    private function fetchProductDetails(string $url): array
    {
        $html = $this->requestHtml($url);
        $crawler = new Crawler($html);

        [$price, $oldPrice] = $this->extractPricesFromCrawler($crawler);

        $descriptionNode = $this->extractDescriptionNode($crawler);
        $descriptionHtml = $descriptionNode?->html() ?? null;
        $cleanDescriptionText = $descriptionHtml
            ? $this->cleaner->htmlToCleanText($descriptionHtml)
            : null;
        [$summaryText, $descriptionText] = $cleanDescriptionText
            ? $this->cleaner->splitSummaryAndDescription($cleanDescriptionText)
            : [null, null];
        $timelineItems = $descriptionHtml
            ? $this->extractTimelineItemsFromDescriptionHtml($descriptionHtml)
            : [];

        if ($timelineItems === [] && $cleanDescriptionText) {
            $timelineItems = $this->extractTimelineItemsFromDescription($cleanDescriptionText);
        }
        $aboutSummary = $timelineItems !== []
            ? $this->extractAboutSummaryFromTimelineItems($timelineItems)
            : null;

        if ($aboutSummary) {
            $summaryText = $aboutSummary;
            $descriptionText = $aboutSummary;
        }
        $structuredMeta = $cleanDescriptionText
            ? $this->cleaner->extractStructuredMeta($cleanDescriptionText)
            : [];

        $details = [
            'title_ar' => $this->safeText($crawler, 'h1'),
            'category_ar' => $this->extractBreadcrumbCategory($crawler),
            'price' => $price,
            'old_price' => $oldPrice,
            'image_path' => $this->extractMainImageUrl($crawler),
            'sku' => $this->safeText($crawler, '.sku'),
            'summary_text' => $summaryText,
            'description_text' => $descriptionText,
            'start_date' => $this->extractStartDate($crawler->text()),
            'attributes' => $this->extractAttributes($crawler),
            'structured_meta' => $structuredMeta,
        ];

        if ($timelineItems !== []) {
            $details['timeline_items'] = $timelineItems;
        }

        if (Schema::hasColumn('courses', 'source_html')) {
            $details['source_html'] = $html;
        }

        return $details;
    }

    private function upsertCategory(?string $nameAr): Category
    {
        $nameAr = $nameAr && trim($nameAr) !== '' ? trim($nameAr) : 'Uncategorized';
        $slug = $this->makeSlugFromText($nameAr);
        $nameEn = $this->translateToEnglish($nameAr) ?? $this->fallbackEnglishFromSlug($slug);

        $category = Category::firstOrNew(['slug' => $slug]);
        $category->setTranslation('name', 'ar', $nameAr);
        $category->setTranslation('name', 'en', $nameEn);
        $category->is_active = true;
        $category->save();

        return $category;
    }

    private function upsertCourse(int $categoryId, array $payload): Course
    {
        $slug = $this->extractSlugFromUrl($payload['source_url'] ?? '', $payload['title_ar'] ?? null);
        $titleAr = $payload['title_ar'] ?? $slug;
        $titleEn = $this->translateToEnglish($titleAr) ?? $this->fallbackEnglishFromSlug($slug);

        $summaryTextAr = $payload['summary_text'] ?? null;
        $descriptionTextAr = $payload['description_text'] ?? null;
        $summaryHtmlAr = $summaryTextAr ? $this->cleaner->toFilamentHtml($summaryTextAr) : '';
        $descriptionHtmlAr = $descriptionTextAr ? $this->cleaner->toFilamentHtml($descriptionTextAr) : '';
        $summaryTextEn = $this->translateToEnglish($summaryTextAr);
        $descriptionTextEn = $this->translateToEnglish($descriptionTextAr);
        $summaryHtmlEn = $summaryTextEn ? $this->cleaner->toFilamentHtml($summaryTextEn) : '';
        $descriptionHtmlEn = $descriptionTextEn ? $this->cleaner->toFilamentHtml($descriptionTextEn) : '';

        $courseData = [
            'category_id' => $categoryId,
            'title' => ['ar' => $titleAr, 'en' => $titleEn],
            'summary' => [
                'ar' => $summaryHtmlAr,
                'en' => $summaryHtmlEn,
            ],
            'description' => [
                'ar' => $descriptionHtmlAr,
                'en' => $descriptionHtmlEn,
            ],
            'slug' => $slug,
            'image_path' => $payload['image_path'] ?? null,
            'price' => $payload['price'] ?? null,
            'start_date' => $payload['start_date'] ?? null,
            'source_url' => $payload['source_url'] ?? null,
            'sku' => $payload['sku'] ?? null,
            'duration_hours' => $payload['duration_hours'] ?? null,
            'duration_group' => $payload['duration_group'] ?? null,
            'days_count' => $payload['days_count'] ?? null,
            'time_from' => $payload['time_from'] ?? null,
            'time_to' => $payload['time_to'] ?? null,
            'delivery_type' => $payload['delivery_type'] ?? null,
            'status' => $payload['status'] ?? CourseStatus::Published,
        ];

        $this->setIfColumnExists($courseData, 'source_html', $payload['source_html'] ?? null);
        $this->setIfColumnExists($courseData, 'attributes', $payload['attributes'] ?? null);
        $this->setIfColumnExists($courseData, 'intro_video_path', $payload['intro_video_path'] ?? null);
        $this->setIfColumnExists($courseData, 'sneak_peek_images', $payload['sneak_peek_images'] ?? null);
        $this->setIfColumnExists($courseData, 'timeline_items', $payload['timeline_items'] ?? null);

        $uniqueCriteria = filled($payload['source_url'] ?? null)
            ? ['source_url' => $payload['source_url']]
            : ['slug' => $slug];

        $course = Course::updateOrCreate($uniqueCriteria, $courseData);

        $this->upsertDiscount($course, $payload['discount'] ?? null);

        return $course;
    }

    /**
     * @return array<int, array{title_ar:string,title_en:string,description_ar:string,description_en:string,image:?string}>
     */
    private function extractTimelineItemsFromDescription(string $cleanDescriptionText): array
    {
        $parsed = $this->cleaner->parseSections($cleanDescriptionText);
        $sections = collect($parsed['sections'] ?? []);

        $targets = [
            'about' => 'التعريف بالدورة',
            'objectives' => 'الأهداف التفصيلية للدورة',
            'audience' => 'الفئة المستهدفة',
            'advantages' => 'مميزات الدورة',
            'outcomes' => 'في نهاية الدورة سيكون المشاركون قادرون على :',
        ];

        $items = [];

        foreach ($targets as $key => $title) {
            $section = $sections->first(function ($item) use ($key): bool {
                return is_array($item) && ($item['key'] ?? null) === $key;
            });

            if (! is_array($section)) {
                continue;
            }

            $lines = collect($section['lines'] ?? [])
                ->map(fn ($line) => trim((string) $line))
                ->filter(fn ($line) => $line !== '')
                ->values()
                ->all();

            if ($lines === []) {
                continue;
            }

            $description = implode("\n", $lines);

            $items[] = [
                'title_ar' => $title,
                'title_en' => $title,
                'description_ar' => $description,
                'description_en' => $description,
                'image' => null,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{title_ar:string,title_en:string,description_ar:string,description_en:string,image:?string}>
     */
    private function extractTimelineItemsFromDescriptionHtml(string $descriptionHtml): array
    {
        $wrapper = new Crawler('<div id="timeline-root">'.$descriptionHtml.'</div>');
        $root = $wrapper->filter('#timeline-root');

        if ($root->count() === 0) {
            return [];
        }

        $targets = [
            'التعريف بالدورة' => ['التعريف بالدورة'],
            'الأهداف التفصيلية للدورة' => ['الأهداف التفصيلية للدورة'],
            'الفئة المستهدفة' => ['الفئة المستهدفة'],
            'مميزات الدورة' => ['مميزات الدورة'],
            'في نهاية الدورة سيكون المشاركون قادرون على :' => ['في نهاية الدورة سيكون المشاركون قادرون على :', 'في نهاية الدورة سيكون المشاركون قادرون على'],
        ];

        $groups = [];
        $currentHeading = null;

        foreach ($root->children() as $node) {
            $nodeCrawler = new Crawler($node);
            $nodeName = mb_strtolower((string) ($node->nodeName ?? ''));

            if ($nodeName === 'h3') {
                $headingText = trim($nodeCrawler->text(''));
                $currentHeading = $this->matchTimelineHeading($headingText, $targets);

                if ($currentHeading && ! array_key_exists($currentHeading, $groups)) {
                    $groups[$currentHeading] = [];
                }

                continue;
            }

            if (! $currentHeading) {
                continue;
            }

            $line = trim($nodeCrawler->text(''));
            if ($line !== '') {
                $groups[$currentHeading][] = $line;
            }
        }

        $items = [];
        foreach ($groups as $title => $lines) {
            if ($lines === []) {
                continue;
            }

            $description = implode("\n", array_values(array_unique($lines)));

            $items[] = [
                'title_ar' => $title,
                'title_en' => $title,
                'description_ar' => $description,
                'description_en' => $description,
                'image' => null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, array<int, string>>  $targets
     */
    private function matchTimelineHeading(string $headingText, array $targets): ?string
    {
        $normalized = trim(rtrim($headingText, " \t\n\r\0\x0B:"));

        foreach ($targets as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = trim(rtrim($alias, " \t\n\r\0\x0B:"));
                if ($normalized === $normalizedAlias) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{title_ar:string,title_en:string,description_ar:string,description_en:string,image:?string}>  $timelineItems
     */
    private function extractAboutSummaryFromTimelineItems(array $timelineItems): ?string
    {
        $aboutItem = collect($timelineItems)->first(function (array $item): bool {
            return ($item['title_ar'] ?? null) === 'التعريف بالدورة';
        });

        if (! is_array($aboutItem)) {
            return null;
        }

        $lines = preg_split('/\R/u', (string) ($aboutItem['description_ar'] ?? '')) ?: [];
        $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), $lines)));

        if ($lines === []) {
            return null;
        }

        return implode("\n", array_slice($lines, 0, 3));
    }

    private function buildPayloadFromCsvRow(array $header, array $row): array
    {
        $record = [];
        foreach ($header as $index => $key) {
            if ($key === '') {
                continue;
            }
            $record[$key] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        $name = $record['name'] ?? null;
        if (! filled($name)) {
            throw new \InvalidArgumentException('Missing required Name field.');
        }

        $categories = $record['categories'] ?? '';
        $category = $this->extractPrimaryCategoryFromCsv($categories);

        $salePrice = $this->normalizePrice($record['sale_price'] ?? null);
        $regularPrice = $this->normalizePrice($record['regular_price'] ?? null);
        $coursePrice = $salePrice ?? $regularPrice;

        $imagePath = $this->extractPrimaryImageFromCsv($record['images'] ?? null);
        $status = $this->parsePublishedStatusFromCsv($record['published'] ?? null);

        return [
            'source_url' => null,
            'title_ar' => $name,
            'category_ar' => $category,
            'price' => $coursePrice,
            'summary_text' => $record['short_description'] ?? null,
            'description_text' => $record['description'] ?? null,
            'image_path' => $imagePath,
            'sku' => $record['sku'] ?? null,
            'duration_group' => $record['groups'] ?? null,
            'status' => $status,
            'discount' => $this->buildDiscountFromPrices($salePrice, $regularPrice),
        ];
    }

    private function normalizeCsvHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = Str::lower(trim($value));
        $value = str_replace(['?', '.', '-'], '', $value);
        $value = preg_replace('/\s+/', '_', $value) ?? $value;

        return $value;
    }

    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function extractPrimaryCategoryFromCsv(?string $value): string
    {
        if (! filled($value)) {
            return 'Uncategorized';
        }

        $parts = preg_split('/[>,|]/', $value) ?: [];
        foreach ($parts as $part) {
            $candidate = trim($part);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Uncategorized';
    }

    private function extractPrimaryImageFromCsv(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $parts = preg_split('/[|,]/', $value) ?: [];
        foreach ($parts as $part) {
            $candidate = trim($part);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function parsePublishedStatusFromCsv(?string $value): CourseStatus
    {
        $normalized = Str::lower(trim((string) $value));

        if (in_array($normalized, ['1', 'yes', 'true', 'publish', 'published'], true)) {
            return CourseStatus::Published;
        }

        return CourseStatus::Draft;
    }

    private function ensureCourseDefaults(Course $course, array $payload): void
    {
        $this->ensureTimelineContent($course, $payload);
        $group = $this->ensureGroup($course, $payload);
        $this->ensureSessions($group);
        $this->ensureLessons($course);
        $this->ensureAttachments($course);
        $this->ensureQuiz($course);
    }

    private function ensureTimelineContent(Course $course, array $payload): void
    {
        $timelineItems = is_array($course->timeline_items) ? $course->timeline_items : [];
        $timelineHighlights = is_array($course->timeline_highlights) ? $course->timeline_highlights : [];

        if ($timelineItems !== [] && $timelineHighlights !== []) {
            return;
        }

        $titleAr = (string) $course->getTranslation('title', 'ar');
        $titleEn = (string) $course->getTranslation('title', 'en');
        $summaryAr = (string) $course->getTranslation('summary', 'ar');
        $summaryEn = (string) $course->getTranslation('summary', 'en');
        $descriptionAr = (string) $course->getTranslation('description', 'ar');
        $descriptionEn = (string) $course->getTranslation('description', 'en');
        $image = $course->image_path ?: ($payload['image_path'] ?? null);

        if ($timelineItems === []) {
            $timelineItems = [
                [
                    'title_ar' => $titleAr !== '' ? $titleAr : 'About this course',
                    'title_en' => $titleEn !== '' ? $titleEn : 'About this course',
                    'description_ar' => $descriptionAr !== '' ? $descriptionAr : $summaryAr,
                    'description_en' => $descriptionEn !== '' ? $descriptionEn : $summaryEn,
                    'image' => $image,
                ],
                [
                    'title_ar' => 'Learning outcomes',
                    'title_en' => 'Learning outcomes',
                    'description_ar' => $summaryAr !== '' ? $summaryAr : $descriptionAr,
                    'description_en' => $summaryEn !== '' ? $summaryEn : $descriptionEn,
                    'image' => $image,
                ],
                [
                    'title_ar' => 'Course logistics',
                    'title_en' => 'Course logistics',
                    'description_ar' => $this->buildTimelineLogisticsHtml($course),
                    'description_en' => $this->buildTimelineLogisticsHtml($course),
                    'image' => $image,
                ],
            ];
        }

        if ($timelineHighlights === []) {
            $timelineHighlights = [
                [
                    'text_ar' => $this->buildTimelineHighlightText($course),
                    'text_en' => $this->buildTimelineHighlightText($course),
                    'icon' => null,
                ],
                [
                    'text_ar' => $this->buildTimelineDurationText($course),
                    'text_en' => $this->buildTimelineDurationText($course),
                    'icon' => null,
                ],
                [
                    'text_ar' => $this->buildTimelineDeliveryText($course),
                    'text_en' => $this->buildTimelineDeliveryText($course),
                    'icon' => null,
                ],
            ];
        }

        $updateData = [];
        $this->setIfColumnExists($updateData, 'timeline_items', $timelineItems);
        $this->setIfColumnExists($updateData, 'timeline_highlights', $timelineHighlights);

        if ($updateData !== []) {
            $course->update($updateData);
        }
    }

    private function buildTimelineLogisticsHtml(Course $course): string
    {
        $parts = [];

        if ($course->duration_hours) {
            $parts[] = 'Duration: '.$course->duration_hours.' hours';
        }

        if ($course->days_count) {
            $parts[] = 'Days: '.$course->days_count;
        }

        if ($course->time_from || $course->time_to) {
            $parts[] = 'Time: '.trim(((string) $course->time_from).' - '.((string) $course->time_to), ' -');
        }

        if ($parts === []) {
            $parts[] = 'Schedule details will be shared before the group starts.';
        }

        $html = '<ul>';
        foreach ($parts as $part) {
            $html .= '<li>'.$part.'</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function buildTimelineHighlightText(Course $course): string
    {
        if ($course->start_date) {
            return 'Start date: '.$course->start_date->format('Y-m-d');
        }

        return 'Start date will be announced soon.';
    }

    private function buildTimelineDurationText(Course $course): string
    {
        if ($course->duration_hours || $course->days_count) {
            $hours = $course->duration_hours ? $course->duration_hours.' hours' : null;
            $days = $course->days_count ? $course->days_count.' days' : null;

            return collect([$hours, $days])->filter()->implode(' - ');
        }

        return 'Flexible duration based on schedule.';
    }

    private function buildTimelineDeliveryText(Course $course): string
    {
        if ($course->delivery_type) {
            $deliveryValue = $course->delivery_type instanceof \BackedEnum
                ? $course->delivery_type->value
                : (string) $course->delivery_type;

            return 'Delivery: '.Str::headline($deliveryValue);
        }

        return 'Delivery mode will be confirmed with the instructor.';
    }

    private function ensureGroup(Course $course, array $payload): CourseGroup
    {
        $existing = $course->groups()->first();
        if ($existing) {
            return $existing;
        }

        $startAt = $course->start_date?->copy()->setTime(18, 0)
            ?? now()->addDays(7)->setTime(18, 0);
        $endAt = $startAt->copy()->addDays(7);
        $title = $startAt->format('d-m-Y');
        $price = $course->price ?? ($payload['price'] ?? 0);
        $groupPriceAmount = (int) round((float) $price);

        return CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['en' => $title, 'ar' => $title],
            'group_price_amount' => $groupPriceAmount,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'capacity' => 25,
            'enrollment_deadline_at' => $startAt->copy()->subDays(2),
            'status' => CourseGroupStatus::Open,
        ]);
    }

    private function ensureSessions(CourseGroup $group): void
    {
        $startAt = $group->start_at ?? now()->addDays(7)->setTime(18, 0);

        CourseGroupSession::updateOrCreate(
            [
                'course_group_id' => $group->id,
                'title' => 'Kickoff session',
            ],
            [
                'start_at' => $startAt->copy()->addDays(1),
                'duration_minutes' => 90,
                'join_url' => 'https://example.com/meet',
                'recording_url' => null,
            ]
        );

        CourseGroupSession::updateOrCreate(
            [
                'course_group_id' => $group->id,
                'title' => 'Workshop clinic',
            ],
            [
                'start_at' => $startAt->copy()->addDays(4),
                'duration_minutes' => 120,
                'join_url' => 'https://example.com/meet',
                'recording_url' => null,
            ]
        );
    }

    private function ensureLessons(Course $course): void
    {
        Lesson::updateOrCreate(
            [
                'course_id' => $course->id,
                'sort_order' => 1,
            ],
            [
                'title' => ['en' => 'Welcome & overview', 'ar' => 'مقدمة ونظرة عامة'],
                'content' => [
                    'en' => '<p>Welcome to the course. This lesson covers the core roadmap.</p>',
                    'ar' => '<p>مرحبًا بك في الدورة. تتناول هذه الوحدة خارطة الطريق الأساسية.</p>',
                ],
                'type' => LessonType::Text,
                'is_preview' => true,
                'video_url' => null,
                'pdf_path' => null,
            ]
        );

        Lesson::updateOrCreate(
            [
                'course_id' => $course->id,
                'sort_order' => 2,
            ],
            [
                'title' => ['en' => 'Core concepts video', 'ar' => 'فيديو المفاهيم الأساسية'],
                'content' => [
                    'en' => '<p>Short video walkthrough with key concepts.</p>',
                    'ar' => '<p>شرح فيديو مختصر لأهم المفاهيم.</p>',
                ],
                'type' => LessonType::Video,
                'video_url' => 'https://example.com/video',
                'is_preview' => false,
                'pdf_path' => null,
            ]
        );

        Lesson::updateOrCreate(
            [
                'course_id' => $course->id,
                'sort_order' => 3,
            ],
            [
                'title' => ['en' => 'Toolkit PDF', 'ar' => 'ملف الأدوات (PDF)'],
                'content' => [
                    'en' => '<p>Download the toolkit PDF for reference.</p>',
                    'ar' => '<p>قم بتنزيل ملف الأدوات للرجوع إليه.</p>',
                ],
                'type' => LessonType::Pdf,
                'pdf_path' => 'files/toolkit.pdf',
                'is_preview' => false,
                'video_url' => null,
            ]
        );
    }

    private function ensureAttachments(Course $course): void
    {
        if ($course->attachments()->where('file_path', 'files/course-outline.pdf')->exists()) {
            return;
        }

        CourseAttachment::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Course outline', 'ar' => 'خطة الدورة'],
            'file_path' => 'files/course-outline.pdf',
            'visibility' => AttachmentVisibility::Public,
        ]);
    }

    private function ensureQuiz(Course $course): void
    {
        $quiz = Quiz::updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'Starter Quiz',
            ],
            [
                'passing_score' => 70,
            ]
        );

        $firstQuestion = QuizQuestion::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'question' => 'What is the first step before starting implementation?',
            ],
            [
                'type' => 'mcq',
                'points' => 1,
            ]
        );

        QuizOption::updateOrCreate(
            [
                'quiz_question_id' => $firstQuestion->id,
                'label' => 'Define scope and requirements',
            ],
            [
                'is_correct' => true,
            ]
        );

        QuizOption::updateOrCreate(
            [
                'quiz_question_id' => $firstQuestion->id,
                'label' => 'Skip planning and start coding',
            ],
            [
                'is_correct' => false,
            ]
        );

        $secondQuestion = QuizQuestion::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'question' => 'What helps keep course progress healthy?',
            ],
            [
                'type' => 'mcq',
                'points' => 1,
            ]
        );

        QuizOption::updateOrCreate(
            [
                'quiz_question_id' => $secondQuestion->id,
                'label' => 'Review lessons and practice consistently',
            ],
            [
                'is_correct' => true,
            ]
        );

        QuizOption::updateOrCreate(
            [
                'quiz_question_id' => $secondQuestion->id,
                'label' => 'Ignore practice and only read titles',
            ],
            [
                'is_correct' => false,
            ]
        );
    }

    private function requestHtml(string $url): string
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::REQUEST_ATTEMPTS) {
            $attempts++;

            try {
                $response = $this->client->get($url);
                $this->sleepBetweenRequests();

                return (string) $response->getBody();
            } catch (GuzzleException $e) {
                $lastException = $e;
                usleep(250000 * $attempts);
            }
        }

        throw $lastException ?? new \RuntimeException('Failed to fetch URL: '.$url);
    }

    private function sleepBetweenRequests(): void
    {
        usleep(random_int(300000, 800000));
    }

    private function looksLikeCategory(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return preg_match('/\p{Arabic}/u', $text) === 1;
    }

    private function isProductUrl(?string $href): bool
    {
        if (! $href) {
            return false;
        }

        return str_contains($href, self::PRODUCT_PATH);
    }

    private function normalizeUrl(string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        $base = 'https://backup.themiran.net';

        if (str_starts_with($href, '/')) {
            return $base.$href;
        }

        return $base.'/'.$href;
    }

    private function findProductContainer(Crawler $link): ?Crawler
    {
        foreach ($link->ancestors() as $ancestor) {
            $candidate = new Crawler($ancestor);
            $text = trim($candidate->text());

            if ($text === '') {
                continue;
            }

            if ($this->extractStartDate($text) || $this->containsPrice($text)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractCategoryFromContainer(?Crawler $container): ?string
    {
        if (! $container) {
            return null;
        }

        $selectors = [
            '.product-category',
            '.posted_in a',
            '.category',
        ];

        foreach ($selectors as $selector) {
            if ($container->filter($selector)->count() > 0) {
                $text = trim($container->filter($selector)->first()->text());
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function extractPricesFromCrawler(Crawler $crawler): array
    {
        $price = null;
        $oldPrice = null;

        if ($crawler->filter('.price del')->count() > 0) {
            $oldPrice = $this->normalizePrice($crawler->filter('.price del')->first()->text());
        }

        if ($crawler->filter('.price ins')->count() > 0) {
            $price = $this->normalizePrice($crawler->filter('.price ins')->first()->text());
        }

        if (! $price && $crawler->filter('.price')->count() > 0) {
            $price = $this->normalizePrice($crawler->filter('.price')->first()->text());
        }

        if (! $price || ! $oldPrice) {
            [$fallbackPrice, $fallbackOldPrice] = $this->extractPricesFromText($crawler->text());
            $price = $price ?? $fallbackPrice;
            $oldPrice = $oldPrice ?? $fallbackOldPrice;
        }

        return [$price, $oldPrice];
    }

    private function extractPricesFromText(string $text): array
    {
        preg_match_all('/\d{1,3}(?:[.,]\d{3})*(?:,\d{2})?|\d+(?:,\d{2})?/u', $text, $matches);

        $numbers = array_values(array_filter(array_map(function (string $match) {
            return $this->normalizePrice($match);
        }, $matches[0] ?? [])));

        if (count($numbers) === 0) {
            return [null, null];
        }

        if (count($numbers) === 1) {
            return [$numbers[0], null];
        }

        return [end($numbers), $numbers[0]];
    }

    private function containsPrice(string $text): bool
    {
        return preg_match('/\d{1,3}(?:[.,]\d{3})*(?:,\d{2})?|\d+(?:,\d{2})?/u', $text) === 1;
    }

    private function normalizePrice(?string $priceText): ?string
    {
        if (! $priceText) {
            return null;
        }

        $clean = preg_replace('/[^\d,\.]/u', '', $priceText);
        if ($clean === '') {
            return null;
        }

        $hasComma = str_contains($clean, ',');
        $hasDot = str_contains($clean, '.');

        if ($hasComma && $hasDot) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif ($hasComma) {
            $clean = str_replace(',', '.', $clean);
        }

        if (! is_numeric($clean)) {
            return null;
        }

        return number_format((float) $clean, 2, '.', '');
    }

    private function extractStartDate(?string $text): ?string
    {
        if (! $text) {
            return null;
        }

        if (preg_match('/Start Date\s*:\s*(\d{4}-\d{2}-\d{2})/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractDescriptionNode(Crawler $crawler): ?Crawler
    {
        $selectors = [
            '#tab-description',
            '.woocommerce-Tabs-panel--description',
            '.wc-tab#tab-description',
        ];

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                return $crawler->filter($selector)->first();
            }
        }

        return null;
    }

    private function extractBreadcrumbCategory(Crawler $crawler): ?string
    {
        $selectors = [
            '.woocommerce-breadcrumb a',
            '.breadcrumb a',
            '.yoast-breadcrumb a',
        ];

        foreach ($selectors as $selector) {
            $links = $crawler->filter($selector);
            if ($links->count() > 0) {
                $text = trim($links->last()->text());
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function extractMainImageUrl(Crawler $crawler): ?string
    {
        $selectors = [
            '.woocommerce-product-gallery a',
            'a[href$=".png"]',
            'a[href$=".jpg"]',
            'a[href$=".jpeg"]',
            'a[href$=".webp"]',
        ];

        foreach ($selectors as $selector) {
            $links = $crawler->filter($selector);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($this->looksLikeImageUrl($href)) {
                    return $this->normalizeUrl($href);
                }
            }
        }

        return null;
    }

    private function looksLikeImageUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        return preg_match('/\.(png|jpe?g|webp)(\?.*)?$/i', $url) === 1;
    }

    private function safeText(Crawler $crawler, string $selector): ?string
    {
        if ($crawler->filter($selector)->count() === 0) {
            return null;
        }

        $text = trim($crawler->filter($selector)->first()->text());

        return $text !== '' ? $text : null;
    }

    private function extractAttributes(Crawler $crawler): array
    {
        if ($crawler->filter('.woocommerce-product-attributes')->count() === 0) {
            return [];
        }

        $attributes = [];
        $crawler->filter('.woocommerce-product-attributes tr')->each(function (Crawler $row) use (&$attributes) {
            $label = $row->filter('th')->count() > 0
                ? trim($row->filter('th')->first()->text())
                : '';
            $value = $row->filter('td')->count() > 0
                ? trim($row->filter('td')->first()->text())
                : '';

            if ($label !== '' && $value !== '') {
                $attributes[$label] = $value;
            }
        });

        return $attributes;
    }

    private function extractSlugFromUrl(string $url, ?string $fallbackTitle = null): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');

        if (str_starts_with($path, 'product/')) {
            $path = trim(substr($path, strlen('product/')), '/');
        }

        if ($path !== '') {
            return $path;
        }

        if ($fallbackTitle) {
            return $this->makeSlugFromText($fallbackTitle);
        }

        return Str::random(8);
    }

    private function makeSlugFromText(string $text): string
    {
        $slug = Str::slug($text, '-', null);

        if ($slug === '') {
            $slug = (string) Str::of($text)
                ->lower()
                ->replaceMatches('/\s+/u', '-')
                ->replaceMatches('/[^\p{L}\p{N}\-]+/u', '')
                ->trim('-');
        }

        return $slug !== '' ? $slug : Str::random(8);
    }

    private function translateToEnglish(?string $arabic): ?string
    {
        if (! $arabic) {
            return null;
        }

        $arabic = trim($arabic);
        if ($arabic === '') {
            return null;
        }

        if (class_exists(\Stichoza\GoogleTranslate\GoogleTranslate::class)) {
            try {
                $translator = new \Stichoza\GoogleTranslate\GoogleTranslate('en', 'ar');
                $translated = $translator->translate($arabic);
                if (is_string($translated) && trim($translated) !== '') {
                    return trim($translated);
                }
            } catch (\Throwable $e) {
                Log::notice('GoogleTranslate failed, using fallback.', ['error' => $e->getMessage()]);
            }
        }

        $apiUrl = env('TRANSLATE_API_URL');
        if ($apiUrl) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(array_filter([
                        'Authorization' => env('TRANSLATE_API_KEY') ? 'Bearer '.env('TRANSLATE_API_KEY') : null,
                        'Accept' => 'application/json',
                    ]))
                    ->post($apiUrl, [
                        'text' => $arabic,
                        'source' => 'ar',
                        'target' => 'en',
                    ]);

                if ($response->ok()) {
                    $data = $response->json();
                    $translated = $data['translatedText']
                        ?? $data['translation']
                        ?? data_get($data, 'data.translations.0.translatedText');

                    if (is_string($translated) && trim($translated) !== '') {
                        return trim($translated);
                    }
                }
            } catch (\Throwable $e) {
                Log::notice('HTTP translation failed, using fallback.', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function fallbackEnglishFromSlug(string $slug): string
    {
        $words = str_replace('-', ' ', $slug);

        return Str::headline($words);
    }

    private function mergeListingAndProduct(array $listing, array $product): array
    {
        $structuredMeta = $product['structured_meta'] ?? [];
        $price = $product['price'] ?? $listing['price'] ?? null;
        $oldPrice = $product['old_price'] ?? $listing['old_price'] ?? null;

        return [
            'source_url' => $listing['source_url'],
            'title_ar' => $product['title_ar'] ?? $listing['title_ar'] ?? null,
            'category_ar' => $product['category_ar'] ?? $listing['category_ar'] ?? null,
            'price' => $price,
            'start_date' => $product['start_date'] ?? $listing['start_date'] ?? null,
            'image_path' => $product['image_path'] ?? null,
            'sku' => $product['sku'] ?? null,
            'summary_text' => $product['summary_text'] ?? null,
            'description_text' => $product['description_text'] ?? null,
            'attributes' => $product['attributes'] ?? [],
            'source_html' => $product['source_html'] ?? null,
            'discount' => $this->buildDiscountFromPrices($price, $oldPrice),
            'duration_hours' => $structuredMeta['duration_hours'] ?? null,
            'duration_group' => $structuredMeta['duration_group'] ?? null,
            'days_count' => $structuredMeta['days_count'] ?? null,
            'time_from' => $structuredMeta['time_from'] ?? null,
            'time_to' => $structuredMeta['time_to'] ?? null,
            'delivery_type' => $structuredMeta['delivery_type'] ?? null,
        ];
    }

    private function setIfColumnExists(array &$data, string $column, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (! Schema::hasColumn('courses', $column)) {
            return;
        }

        if (is_array($value) && $value === []) {
            return;
        }

        $data[$column] = $value;
    }

    private function buildDiscountFromPrices(?string $price, ?string $oldPrice): ?array
    {
        if ($price === null || $oldPrice === null) {
            return null;
        }

        $priceValue = (float) $price;
        $oldValue = (float) $oldPrice;

        if ($oldValue <= $priceValue) {
            return null;
        }

        $amount = $oldValue - $priceValue;

        return [
            'type' => Discount::TYPE_FIXED,
            'amount' => $amount,
            'max_amount' => $amount,
            'is_active' => true,
        ];
    }

    private function upsertDiscount(Course $course, ?array $discount): void
    {
        if (! $discount) {
            $course->discounts()->detach();

            return;
        }

        $record = $course->discounts()->first();
        if ($record) {
            $record->update([
                'type' => $discount['type'],
                'amount' => $discount['amount'],
                'max_amount' => $discount['max_amount'] ?? null,
                'is_active' => $discount['is_active'] ?? true,
            ]);
        } else {
            $record = Discount::query()->create([
                'type' => $discount['type'],
                'amount' => $discount['amount'],
                'max_amount' => $discount['max_amount'] ?? null,
                'is_active' => $discount['is_active'] ?? true,
            ]);
            $course->discounts()->syncWithoutDetaching([$record->id]);
        }
    }
}
