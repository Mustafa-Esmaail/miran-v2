<?php

namespace Database\Seeders;

use App\Models\OrderItemSeat;
use Illuminate\Database\Seeder;

class OrderItemSeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        OrderItemSeat::factory()->count(3)->create();
    }
}
