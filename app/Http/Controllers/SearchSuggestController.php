<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }

        $courses = Course::query()
            ->published()
            ->with('category')
            ->matchingSearch($q)
            ->limit(5)
            ->get();

        $locale = app()->getLocale();

        $fallback = $locale === 'ar' ? 'en' : 'ar';

        $results = $courses->map(fn (Course $course) => [
            'title' => $course->getTranslation('title', $locale, false)
                       ?: $course->getTranslation('title', $fallback, false)
                       ?: $course->slug,
            'category' => $course->category
                ? ($course->category->getTranslation('name', $locale, false)
                   ?: $course->category->getTranslation('name', $fallback, false))
                : null,
            'image' => $course->image_url,
            'url' => route('courses.show', $course->slug),
        ]);

        return response()->json($results);
    }
}
