<?php

namespace Database\Seeders;

use App\Models\OrderAttribution;
use Illuminate\Database\Seeder;

class OrderAttributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrderAttribution::factory()
            ->count(3)
            ->create();
    }
}
