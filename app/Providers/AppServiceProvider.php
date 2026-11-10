<?php

namespace App\Providers;

use App\Enums\ImpersonationEventType;
use App\Logging\TelegramErrorNotifier;
use App\Models\ApiClient;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\CourseGroupSession;
use App\Models\Deal;
use App\Models\Enrollment;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ServiceRequest;
use App\Observers\CertificateObserver;
use App\Observers\CertificateRequestObserver;
use App\Observers\CourseGroupSessionObserver;
use App\Observers\DealObserver;
use App\Observers\EnrollmentObserver;
use App\Observers\MenuItemObserver;
use App\Observers\MenuObserver;
use App\Observers\ServiceRequestObserver;
use App\Services\ImpersonationLogService;
use App\Services\Settings\MailSettingsApplier;
use App\Services\Settings\SettingsService;
use App\Support\SheetRowContext;
use App\View\Composers\AppLayoutComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SheetRowContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(MailSettingsApplier $mailSettingsApplier): void
    {
        if (Schema::hasTable('settings')) {
            $mailSettingsApplier->apply();
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if ($this->app->runningInConsole()) {
            $siteUrl = Schema::hasTable('settings')
                ? app(SettingsService::class)->siteUrl()
                : config('app.url');

            URL::forceRootUrl($siteUrl);
        }

        Enrollment::observe(EnrollmentObserver::class);
        CourseGroupSession::observe(CourseGroupSessionObserver::class);
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        ServiceRequest::observe(ServiceRequestObserver::class);
        Deal::observe(DealObserver::class);
        CertificateRequest::observe(CertificateRequestObserver::class);
        Certificate::observe(CertificateObserver::class);
        View::composer('layouts.app', AppLayoutComposer::class);

        View::prependNamespace('filament-tables', resource_path('views/vendor/filament-tables'));

        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === 'web' && request()->hasSession()) {
                session()->flash('gtm_login', true);
            }
        });

        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event): void {
            app(ImpersonationLogService::class)->record(
                adminId: (int) $event->impersonator->getAuthIdentifier(),
                studentId: (int) $event->impersonated->getAuthIdentifier(),
                eventType: ImpersonationEventType::Started,
            );
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event): void {
            app(ImpersonationLogService::class)->record(
                adminId: (int) $event->impersonator->getAuthIdentifier(),
                studentId: (int) $event->impersonated->getAuthIdentifier(),
                eventType: ImpersonationEventType::Ended,
            );
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            try {
                $message = $event->message;
                $to = array_keys($message->getTo() ?? []);
                Log::channel('mail')->info('mail.sent', [
                    'to' => $to,
                    'subject' => $message->getSubject(),
                ]);
            } catch (\Throwable) {
            }
        });

        Event::listen(MessageLogged::class, [TelegramErrorNotifier::class, 'handle']);

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api-clients', function (Request $request): Limit {
            $client = $request->attributes->get('api_client');

            return $client instanceof ApiClient
                ? Limit::perMinute($client->rate_limit_per_minute)->by('api-client:'.$client->id)
                : Limit::perMinute(10)->by($request->ip());
        });
    }
}
