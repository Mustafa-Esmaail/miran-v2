<?php

use App\Http\Controllers\Admin\CourseGroupImportTemplateController;
use App\Http\Controllers\Admin\StudentImportTemplateController;
use App\Http\Controllers\AttachmentViewController;
use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductRedirectController;
use App\Http\Controllers\SearchSuggestController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Account\WishlistTab;
use App\Livewire\BankTransferUpload;
use App\Livewire\Blog\BlogDetail;
use App\Livewire\Blog\BlogList;
use App\Livewire\Bundles\BundleGroupSelection;
use App\Livewire\Bundles\BundlesIndex;
use App\Livewire\Cart\CartPanel;
use App\Livewire\Checkout\CheckoutForm;
use App\Livewire\Courses\CourseShow;
use App\Livewire\Courses\CoursesIndex;
use App\Livewire\Dashboard\CoursePlayer\Assignment as DashboardCoursePlayerAssignment;
use App\Livewire\Dashboard\CoursePlayer\Attendance as DashboardCoursePlayerAttendance;
use App\Livewire\Dashboard\CoursePlayer\Day as DashboardCoursePlayerDay;
use App\Livewire\Dashboard\CoursePlayer\Lesson as DashboardCoursePlayerLesson;
use App\Livewire\Dashboard\CoursePlayer\Main as DashboardCoursePlayerMain;
use App\Livewire\Dashboard\CoursePlayer\Quiz as DashboardCoursePlayerQuiz;
use App\Livewire\Dashboard\CoursePlayer\Session as DashboardCoursePlayerSession;
use App\Livewire\DashboardCertificateForm;
use App\Livewire\DashboardCertificates;
use App\Livewire\DashboardDeleteAccount;
use App\Livewire\DashboardDownloads;
use App\Livewire\DashboardMessages;
use App\Livewire\DashboardMyCourses;
use App\Livewire\DashboardOrderRedirecting;
use App\Livewire\DashboardOrders;
use App\Livewire\DashboardOrderShow;
use App\Livewire\DashboardPaymentReturn;
use App\Livewire\DashboardProfile;
use App\Livewire\NotificationsCenter;
use App\Livewire\PaymentFailed;
use App\Livewire\PaymentSuccess;
use Illuminate\Support\Facades\Route;

// Admin-initiated "login as student" impersonation (registers GET /impersonate/take/{id}/{guardName?}
// and GET /impersonate/leave). Registered before the throttled group so it isn't shadowed by the
// catch-all /{slug} route below.
Route::impersonate();

// Public website sitemap. Outside the throttled group so crawlers aren't rate-limited,
// and before the catch-all /{slug} route below.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Served dynamically (no public/robots.txt) so the Sitemap line follows APP_URL per environment.
Route::get('/robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Disallow: /admin',
    'Disallow: /dashboard',
    'Disallow: /cart',
    'Disallow: /checkout',
    'Disallow: /livewire',
    '',
    'Sitemap: '.route('sitemap'),
    '',
]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

// group  rate 60 request per minute

Route::middleware(['throttle:60,1'])->group(function () {

    Route::get('/', [PageController::class, 'show'])->defaults('slug', 'home')->name('home');
    Route::get('/courses', CoursesIndex::class)->name('courses.index');
    Route::get('/search', CoursesIndex::class)->name('courses.search');
    Route::get('/search-suggest', SearchSuggestController::class)->name('search.suggest');
    Route::get('/courses/{slug}', CourseShow::class)->name('courses.show');
    Route::get('/attachments/{attachment}/view', AttachmentViewController::class)->name('attachments.view');
    // Route::get('/pdf-viewer-preview', function () {
    //     return view('pages.pdf-viewer', [
    //         'title' => 'Preview PDF',
    //         'fileUrl' => asset('preview-tmp/sample.pdf'),
    //         'ctaLabel' => 'Buy the course',
    //         'ctaUrl' => route('courses.index'),
    //     ]);
    // })->name('pdf-viewer.preview');
    Route::get('/cart', CartPanel::class)->name('cart');
    Route::get('/checkout', CheckoutForm::class)->name('checkout');
    Route::get('/bundles', BundlesIndex::class)->name('bundles.index');
    Route::get('/thank-you', fn () => view('pages.thank-you'))->name('thank-you');
    Route::get('/blog', BlogList::class)->name('blog.index');
    Route::get('/blog/{slug}', BlogDetail::class)->name('blog.show');
    Route::get('/dashboard-center', function () {
        return view('dashboads-center');
    })->name('dashboard.center');

    Route::get('/dashboard-center/performance', function () {
        return view('performance');
    })->name('dashboard.performance');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/login/email/{user}', EmailLoginController::class)->name('login.email');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
    Route::get('/lang/{locale}', function (string $locale) {
        $supported = ['en', 'ar'];
        abort_unless(in_array($locale, $supported, true), 404);

        session(['app_locale' => $locale, 'admin_locale' => $locale]);
        app()->setLocale($locale);
        if (auth('web')->check()) {
            auth('web')->user()->update(['locale' => $locale]);
        }
        if (auth('admin')->check()) {
            auth('admin')->user()->update(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('lang.switch');

    Route::get('/bundles/{bundle:slug}', BundleGroupSelection::class)
        ->name('bundles.select-groups');

    Route::middleware(['auth:web'])->group(function () {
        Route::get('/dashboard', DashboardMyCourses::class)->name('dashboard');
        Route::get('/dashboard/my-courses', DashboardMyCourses::class)->name('dashboard.courses');
        Route::get('/dashboard/courses/{course_slug}/{group}', DashboardCoursePlayerMain::class)->name('dashboard.course-player');
        Route::get('/dashboard/courses/{course_slug}/{group}/days/{day}', DashboardCoursePlayerDay::class)->name('dashboard.course-player.day');
        Route::get('/dashboard/courses/{course_slug}/{group}/lessons/{lesson}', DashboardCoursePlayerLesson::class)->name('dashboard.course-player.lesson');
        Route::get('/dashboard/courses/{course_slug}/{group}/sessions/{session}', DashboardCoursePlayerSession::class)->name('dashboard.course-player.session');
        Route::get('/dashboard/courses/{course_slug}/{group}/quizzes/{quiz}', DashboardCoursePlayerQuiz::class)->name('dashboard.course-player.quiz');
        Route::get('/dashboard/courses/{course_slug}/{group}/attendance', DashboardCoursePlayerAttendance::class)->name('dashboard.course-player.attendance');
        Route::get('/dashboard/courses/{course_slug}/{group}/assignments/{assignment}', DashboardCoursePlayerAssignment::class)->name('dashboard.course-player.assignment');
        Route::get('/dashboard/orders', DashboardOrders::class)->name('dashboard.orders');
        Route::get('/dashboard/orders/{order_no}/redirecting', DashboardOrderRedirecting::class)->name('dashboard.orders.redirecting');
        Route::get('/dashboard/payments/return/{provider}/{order_no}', DashboardPaymentReturn::class)->name('dashboard.payments.return');
        Route::get('/order-received/{order_no}', PaymentSuccess::class)->name('payment-success');
        Route::get('/payment-failed/{order_no}', PaymentFailed::class)->name('payment-failed');
        Route::get('/dashboard/orders/{order_no}', DashboardOrderShow::class)->name('dashboard.orders.show');
        Route::get('/dashboard/downloads', DashboardDownloads::class)->name('dashboard.downloads');
        Route::get('/dashboard/certificates', DashboardCertificates::class)->name('dashboard.certificates');
        Route::get('/dashboard/certificate-form', DashboardCertificateForm::class)->name('dashboard.certificate-form');
        Route::get('/dashboard/delete-account', DashboardDeleteAccount::class)->name('dashboard.delete-account');
        Route::get('/dashboard/notifications', NotificationsCenter::class)->name('dashboard.notifications');
        Route::get('/dashboard/messages', DashboardMessages::class)->name('dashboard.messages');
        Route::get('/dashboard/messages/{room}', DashboardMessages::class)->name('dashboard.messages.room');
        Route::get('/dashboard/profile', DashboardProfile::class)->name('dashboard.profile');
        Route::get('/dashboard/wishlist', WishlistTab::class)->name('dashboard.wishlist');
        Route::get('/bank-transfer/{order_no}', BankTransferUpload::class)->name('bank-transfer.upload');
    });

    Route::middleware(['auth:admin'])->group(function () {
        // Namespaced under /admin/import-templates/* (not /admin/{resource}/import-template)
        // so these literal routes can never collide with a Filament resource's own
        // /admin/{resource}/{record} "view" route (e.g. record="import-template").
        Route::get('/admin/import-templates/students', StudentImportTemplateController::class)
            ->name('admin.students.import-template');
        Route::get('/admin/import-templates/course-groups', CourseGroupImportTemplateController::class)
            ->name('admin.course-groups.import-template');
        Route::get('/admin/import-templates/crm-tasks', \App\Http\Controllers\Admin\CrmTaskImportTemplateController::class)
            ->name('admin.crm-tasks.import-template');
        Route::get('/admin/import-templates/service-requests', \App\Http\Controllers\Admin\ServiceRequestImportTemplateController::class)
            ->name('admin.service-requests.import-template');
        Route::get('/admin/import-templates/quizzes', \App\Http\Controllers\Admin\QuizImportTemplateController::class)
            ->name('admin.quizzes.import-template');
        Route::get('/admin/import-templates/quiz-questions', \App\Http\Controllers\Admin\QuizQuestionsImportTemplateController::class)
            ->name('admin.quiz-questions.import-template');
        Route::get('/admin/import-templates/countries', \App\Http\Controllers\Admin\CountryImportTemplateController::class)
            ->name('admin.countries.import-template');
        Route::get('/admin/import-templates/qualifications', \App\Http\Controllers\Admin\QualificationImportTemplateController::class)
            ->name('admin.qualifications.import-template');
        Route::get('/admin/import-templates/employees', \App\Http\Controllers\Admin\EmployeeImportTemplateController::class)
            ->name('admin.employees.import-template');
        Route::get('/admin/import-templates/users', \App\Http\Controllers\Admin\UserImportTemplateController::class)
            ->name('admin.users.import-template');

        // Landing versions moved to draft are not public; admins open them here.
        Route::get('/admin/landing-previews/{page:slug}', [PageController::class, 'preview'])
            ->name('admin.pages.preview');
    });

    Route::get('/product/{slug}', ProductRedirectController::class)->name('product.redirect');

    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '^(?!courses|bundles|blog|cart|checkout|login|register|forgot-password|reset-password|password|dashboard|bank-transfer|lang|filament|admin|api|livewire|storage|impersonate).*$')
        ->name('pages.show');
});
