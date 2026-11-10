<?php

namespace App\Http\Controllers;

use App\Livewire\Bundles\BundleGroupSelection;
use App\Livewire\Courses\CourseShow;
use App\Models\Bundle;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductRedirectController extends Controller
{
    public function __invoke(string $slug, Request $request): Response
    {
        if (strtolower($slug) === 'kpi') {
            $slug = 'kpis-online';
        }

        $course = Course::query()->where('slug', $slug)->first();

        if ($course) {
            $request->route()->setParameter('slug', $course->slug);

            return app(CourseShow::class)();
        }

        $bundle = Bundle::query()->where('slug', $slug)->first();

        if ($bundle) {
            $request->route()->setParameter('bundle', $bundle->id);

            return app(BundleGroupSelection::class)();
        }

        abort(404);
    }
}
