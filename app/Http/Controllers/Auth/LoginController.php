<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        $this->clearAdminIntended();
        $loginCourses = Course::published()
            ->withCount('lessons')
            ->latest()
            ->take(2)
            ->get();
        $primaryCourse = $loginCourses->get(0);
        $secondaryCourse = $loginCourses->get(1);

        return view('auth.login', [
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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');
        $credentials['status'] = User::STATUS_ACTIVE;
        $guestSessionId = $request->session()->getId();

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            app(CartService::class)->mergeGuestCartIntoUser($guestSessionId, Auth::guard('web')->id());
            $this->clearAdminIntended();

            return redirect()->intended(route('dashboard'));
        }

        $user = User::query()->where('email', $credentials['email'])->first();
        if ($user && $user->status !== User::STATUS_ACTIVE) {
            return back()
                ->withErrors(['email' => __('auth.account_inactive')])
                ->onlyInput('email');
        }

        return back()
            ->withErrors(['email' => __('auth.failed')])
            ->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Use regenerate instead of invalidate: invalidate() destroys the entire shared session,
        // which also kills any concurrent admin session and causes 419 on admin Livewire requests.
        $request->session()->regenerate(true);

        return redirect()->route('home');
    }

    private function courseImageUrl(?Course $course): ?string
    {
        if (! $course) {
            return null;
        }

        $imageUrl = $course->image_url;

        return $imageUrl;
    }

    private function clearAdminIntended(): void
    {
        $intended = session('url.intended');
        if (! $intended) {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? '';
        if (str_starts_with($path, '/admin') || str_contains($path, '/filament')) {
            session()->forget('url.intended');
        }
    }
}
