<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        $loginCourses = Course::published()
            ->withCount('lessons')
            ->latest()
            ->take(2)
            ->get();
        $primaryCourse = $loginCourses->get(0);
        $secondaryCourse = $loginCourses->get(1);

        return view('auth.register', [
            'primaryCourse' => $primaryCourse,
            'secondaryCourse' => $secondaryCourse,
            'primaryCourseImageUrl' => $this->courseImageUrl($primaryCourse),
            'secondaryCourseImageUrl' => $this->courseImageUrl($secondaryCourse),
            'studentCount' => class_exists(User::class) ? User::count() : 0,
            'loginTopbarMessage' => __('web.home.topbar_message'),
            'loginNavLinks' => [
                ['label' => __('web.nav.home'), 'href' => route('home'), 'active' => false, 'new_tab' => false],
                ['label' => __('web.nav.courses'), 'href' => route('courses.index'), 'active' => false, 'new_tab' => false],
                ['label' => __('web.nav.about'), 'href' => route('home').'#about', 'active' => false, 'new_tab' => false],
                ['label' => __('web.nav.corporate_training'), 'href' => route('home').'#corporate', 'active' => false, 'new_tab' => false],
                ['label' => __('web.nav.complaints'), 'href' => route('home').'#complaints', 'active' => false, 'new_tab' => false],
                ['label' => __('web.nav.contact_request'), 'href' => route('home').'#contact', 'active' => false, 'new_tab' => false],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $guestSessionId = $request->session()->getId();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => app()->getLocale(),
        ]);

        Auth::guard('web')->login($user);

        app(CartService::class)->mergeGuestCartIntoUser($guestSessionId, $user->id);

        return redirect()->route('dashboard');
    }

    private function courseImageUrl(?Course $course): ?string
    {
        if (! $course) {
            return null;
        }

        return $course->image_url;
    }
}
