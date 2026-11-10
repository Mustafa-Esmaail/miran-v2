<?php

namespace Database\Factories;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = $this->faker->randomElement(ServiceRequestSource::cases());
        $departmentName = match ($source) {
            ServiceRequestSource::Lead,
            ServiceRequestSource::CorporateTraining,
            ServiceRequestSource::Form,
            ServiceRequestSource::WhatsApp,
            ServiceRequestSource::Phone,
            ServiceRequestSource::Manual,
            ServiceRequestSource::LandingPage => 'Sales',
            ServiceRequestSource::Contact,
            ServiceRequestSource::Complaint => 'Support',
        };

        $teamId = Team::query()
            ->whereHas('department', fn ($query) => $query->where('name', $departmentName))
            ->inRandomOrder()
            ->value('id') ?? Team::factory()->create()->id;

        return [
            'source' => $source,
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'message' => $this->faker->sentence(),
            'status' => ServiceRequestStatus::default()?->slug ?? 'new',
            'team_id' => $teamId,
            'priority' => $this->faker->randomElement(ServiceRequestPriority::cases()),
            'last_activity_at' => now(),
        ];
    }
}
