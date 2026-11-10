<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Services\EmailLoginLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailLoginController extends Controller
{
    public function __construct(private readonly EmailLoginLinkService $loginLinks) {}

    public function __invoke(Request $request, string $user): RedirectResponse
    {
        $redirectPath = $this->loginLinks->safeRedirectPath($request->query('redirect'));

        if (Auth::guard('web')->check()) {
            return redirect($redirectPath);
        }

        if (! $request->hasValidSignature(absolute: false)) {
            return $this->redirectToLogin($redirectPath, __('auth.login_link_expired'));
        }

        $student = User::query()->find($user);

        if (! $student) {
            return $this->redirectToLogin($redirectPath, __('auth.login_link_expired'));
        }

        if ($student->status !== User::STATUS_ACTIVE) {
            return $this->redirectToLogin($redirectPath, __('auth.account_inactive'));
        }

        $guestSessionId = $request->session()->getId();

        Auth::guard('web')->login($student);
        $request->session()->regenerate();
        app(CartService::class)->mergeGuestCartIntoUser($guestSessionId, $student->getKey());

        return redirect($redirectPath);
    }

    private function redirectToLogin(string $redirectPath, string $message): RedirectResponse
    {
        session()->put('url.intended', url($redirectPath));

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
