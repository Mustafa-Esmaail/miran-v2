<?php

namespace Database\Seeders;

use App\Models\ServiceRequestActivity;
use Illuminate\Database\Seeder;

class ServiceRequestActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceRequestActivity::factory()
            ->count(10)
            ->create();
    }
}
