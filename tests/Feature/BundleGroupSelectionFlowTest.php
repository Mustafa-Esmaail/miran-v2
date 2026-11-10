<?php

namespace Tests\Feature;

use App\Livewire\Bundles\BundleGroupSelection;
use App\Models\Bundle;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BundleGroupSelectionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_group_selection_adds_bundle_with_selected_groups_to_cart(): void
    {
        [$user, $bundle, $courseA, $courseB, $groupA, $groupB] = $this->seedBundleData();

        Livewire::actingAs($user)
            ->test(BundleGroupSelection::class, ['bundle' => $bundle->slug])
            ->set('selectedGroups.'.$courseA->id, (string) $groupA->id)
            ->set('selectedGroups.'.$courseB->id, (string) $groupB->id)
            ->call('confirmAddToCart')
            ->assertRedirect(route('cart'));

        $item = CartItem::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
            ->where('purchasable_type', Bundle::class)
            ->where('purchasable_id', $bundle->id)
            ->firstOrFail();

        $this->assertSame([
            $courseA->id => $groupA->id,
            $courseB->id => $groupB->id,
        ], data_get($item->selection_snapshot, 'bundle_course_groups'));
    }

    /**
     * @return array{0:User,1:Bundle,2:Course,3:Course,4:CourseGroup,5:CourseGroup}
     */
    private function seedBundleData(): array
    {
        $user = User::factory()->create();

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'bundle-category',
            'is_active' => true,
        ]);

        $courseA = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course A'],
            'summary' => ['en' => 'Summary A'],
            'description' => ['en' => 'Description A'],
            'slug' => 'course-a',
            'status' => 'published',
        ]);
        $courseB = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course B'],
            'summary' => ['en' => 'Summary B'],
            'description' => ['en' => 'Description B'],
            'slug' => 'course-b',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $courseA->id,
            'title' => ['en' => 'Group A1'],
            'status' => 'open',
            'group_price_amount' => 1000,
            'enrollment_deadline_at' => now()->addDays(7),
        ]);
        $groupB = CourseGroup::query()->create([
            'course_id' => $courseB->id,
            'title' => ['en' => 'Group B1'],
            'status' => 'open',
            'group_price_amount' => 1200,
            'enrollment_deadline_at' => now()->addDays(7),
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
        $bundle->courses()->sync([$courseA->id, $courseB->id]);

        return [$user, $bundle, $courseA, $courseB, $groupA, $groupB];
    }
}
