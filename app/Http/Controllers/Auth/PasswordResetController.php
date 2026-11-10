<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        $loginCourses = Course::published()
            ->withCount('lessons')
            ->latest()
            ->take(2)
            ->get();
        $primaryCourse = $loginCourses->get(0);
        $secondaryCourse = $loginCourses->get(1);

        return view('auth.password-request', [
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
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('users')->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function edit(Request $request, string $token): View
    {
        $loginCourses = Course::published()
            ->withCount('lessons')
            ->latest()
            ->take(2)
            ->get();
        $primaryCourse = $loginCourses->get(0);
        $secondaryCourse = $loginCourses->get(1);

        return view('auth.password-reset', [
            'token' => $token,
            'email' => $request->query('email'),
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

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    private function courseImageUrl(?Course $course): ?string
    {
        if (! $course) {
            return null;
        }

        return $course->image_url;
    }
}
