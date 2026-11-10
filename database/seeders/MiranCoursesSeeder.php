<?php

namespace Database\Seeders;

use App\Services\MiranCourseImporter;
use Illuminate\Database\Seeder;

class MiranCoursesSeeder extends Seeder
{
    public function run(): void
    {
        app(MiranCourseImporter::class)->import();
    }
}
