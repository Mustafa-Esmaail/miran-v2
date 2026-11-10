<?php

namespace Tests\Feature;

use App\Livewire\DashboardCertificateForm;
use App\Models\Category;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCertificateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_qualification_and_english_middle_names_are_required(): void
    {
        $user = User::factory()->create();
        ['group' => $group] = $this->createEnrollmentFor($user);

        Livewire::actingAs($user)
            ->test(DashboardCertificateForm::class, ['group' => $group->id])
            ->set('firstNameEn', 'John')
            ->set('lastNameEn', 'Doe')
            ->call('submit')
            ->assertHasErrors(['qualification', 'secondNameEn', 'thirdNameEn']);
    }

    public function test_first_submission_creates_a_certificate_request_for_every_enrolled_group(): void
    {
        $user = User::factory()->create();
        ['course' => $courseA, 'group' => $groupA] = $this->createEnrollmentFor($user);
        ['group' => $groupB] = $this->createEnrollmentFor($user, $courseA);

        $this->fillForm(Livewire::actingAs($user)->test(DashboardCertificateForm::class, ['group' => $groupA->id]))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(2, CertificateRequest::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupA->id,
            'qualification' => 'Bachelor',
        ]);
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupB->id,
            'qualification' => 'Bachelor',
        ]);
    }

    public function test_buying_a_new_course_after_submitting_gets_its_own_certificate_request_on_next_submit(): void
    {
        $user = User::factory()->create();
        ['course' => $courseA, 'group' => $groupA] = $this->createEnrollmentFor($user);

        $this->fillForm(Livewire::actingAs($user)->test(DashboardCertificateForm::class, ['group' => $groupA->id]))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, CertificateRequest::query()->where('user_id', $user->id)->count());

        // User buys a new course after already having a certificate request.
        ['group' => $groupB] = $this->createEnrollmentFor($user, $courseA);

        $this->fillForm(Livewire::actingAs($user)->test(DashboardCertificateForm::class, ['group' => $groupA->id]))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(2, CertificateRequest::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupA->id,
            'qualification' => 'Bachelor',
        ]);
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupB->id,
            'qualification' => 'Bachelor',
        ]);
    }

    public function test_editing_after_first_submission_only_updates_the_selected_group(): void
    {
        $user = User::factory()->create();
        ['course' => $courseA, 'group' => $groupA] = $this->createEnrollmentFor($user);
        ['group' => $groupB] = $this->createEnrollmentFor($user, $courseA);

        $this->fillForm(Livewire::actingAs($user)->test(DashboardCertificateForm::class, ['group' => $groupA->id]))
            ->call('submit')
            ->assertHasNoErrors();

        $this->fillForm(Livewire::actingAs($user)->test(DashboardCertificateForm::class, ['group' => $groupA->id]))
            ->set('qualification', 'Master')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(2, CertificateRequest::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupA->id,
            'qualification' => 'Master',
        ]);
        $this->assertDatabaseHas('certificate_requests', [
            'user_id' => $user->id,
            'course_group_id' => $groupB->id,
            'qualification' => 'Bachelor',
        ]);
    }

    private function fillForm(\Livewire\Features\SupportTesting\Testable $component): \Livewire\Features\SupportTesting\Testable
    {
        return $component
            ->set('idNumber', '1234567890')
            ->set('firstNameAr', 'أحمد')
            ->set('secondNameAr', 'محمد')
            ->set('thirdNameAr', 'علي')
            ->set('lastNameAr', 'السالم')
            ->set('birthDate', '1990-01-01')
            ->set('gender', 'male')
            ->set('nationality', 'السعودية')
            ->set('qualification', 'Bachelor')
            ->set('email', 'student@example.com')
            ->set('phone', '0500000000')
            ->set('firstNameEn', 'Ahmed')
            ->set('secondNameEn', 'Mohammed')
            ->set('thirdNameEn', 'Ali')
            ->set('lastNameEn', 'Alsalem');
    }

    /**
     * @return array{course: Course, group: CourseGroup}
     */
    private function createEnrollmentFor(User $user, ?Course $course = null): array
    {
        if (! $course) {
            $category = Category::query()->create([
                'name' => ['en' => 'Category'],
                'slug' => 'category-'.Str::uuid(),
                'is_active' => true,
            ]);

            $course = Course::query()->create([
                'category_id' => $category->id,
                'title' => ['en' => 'Course'],
                'slug' => 'course-'.Str::uuid(),
                'status' => 'published',
            ]);
        }

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course group',
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        return ['course' => $course, 'group' => $group];
    }
}
