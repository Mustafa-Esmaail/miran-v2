<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard as AdminDashboard;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetAdminSessionCookie;
use App\Services\Settings\SettingsService;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Forms\Components\RichEditor;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Validation\ValidationException;
use OsamaAtef\DrilldownSidebar\DrilldownSidebarPlugin;

// use Illuminate\Foundation\Vite;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        RichEditor::configureUsing(function (RichEditor $editor): void {
            $editor
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private');
        });

        BasePage::$reportValidationErrorUsing = function (ValidationException $exception): void {
            $messages = collect($exception->errors())
                ->flatten()
                ->filter()
                ->take(3)
                ->implode("\n");

            // Notification::make()
            //     ->danger()
            //     ->title(__('admin.validation.title'))
            //     ->body($messages !== '' ? __('admin.validation.body')."\n".$messages : __('admin.validation.body'))
            //     ->persistent()
            //     ->send();
        };
    }

    public function panel(Panel $panel): Panel
    {

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(\App\Filament\Auth\Register::class)
            ->passwordReset()
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('IBM Plex Sans Arabic', provider: GoogleFontProvider::class)
            ->colors([
                'primary' => [
                    50 => '#FAF6F2',
                    100 => '#F3EAE2',
                    200 => '#E8D6C6',
                    300 => '#DDB898',
                    400 => '#C69C7A',
                    500 => '#B08465',
                    600 => '#9A7259',
                    700 => '#7A5843',
                    800 => '#5F4434',
                    900 => '#463227',
                    950 => '#2C1F18',
                ],
                'success' => [
                    50 => '#F0F7F2',
                    100 => '#E1EFE6',
                    200 => '#C3DFCC',
                    300 => '#9CCFAE',
                    400 => '#6CC08C',
                    500 => '#509A6D',
                    600 => '#3F7A57',
                    700 => '#336347',
                    800 => '#284E38',
                    900 => '#1B3325',
                    950 => '#10201A',
                ],
                'danger' => [
                    50 => '#FBF1EF',
                    100 => '#F6E2DF',
                    200 => '#EEC3BD',
                    300 => '#EAA197',
                    400 => '#E57D70',
                    500 => '#CB5F51',
                    600 => '#B4483C',
                    700 => '#933A31',
                    800 => '#722D26',
                    900 => '#3D201C',
                    950 => '#2A1512',
                ],
                'warning' => [
                    50 => '#FCF7EE',
                    100 => '#F8EEDC',
                    200 => '#F0DBB4',
                    300 => '#E9C484',
                    400 => '#E0AA55',
                    500 => '#C48C33',
                    600 => '#A8721E',
                    700 => '#875B18',
                    800 => '#684612',
                    900 => '#3A2D17',
                    950 => '#261C0C',
                ],
                'info' => Color::hex('#7B2BC9'),
                'gray' => [
                    50 => '#F7F8F7',
                    100 => '#EEF0EF',
                    200 => '#E2E5E3',
                    300 => '#CDD3D1',
                    400 => '#A3ACA9',
                    500 => '#6B716F',
                    600 => '#535B58',
                    700 => '#3A4945',
                    800 => '#2B3936',
                    900 => '#1B2321',
                    950 => '#141A19',
                ],
            ])
            ->brandName(fn () => null)
            ->brandLogo(function (): string {
                $logoUrl = app(SettingsService::class)->logoUrl();

                return $logoUrl ?: asset('img/logo.png');
            })
            ->favicon(function (): string {
                $faviconUrl = app(SettingsService::class)->faviconUrl();

                return $faviconUrl ?: asset('img/favicon.ico');
            })
            ->brandLogoHeight('84px')
            ->plugin(
                AuthUIEnhancerPlugin::make()
                    ->showEmptyPanelOnMobile(false)
                    ->formPanelPosition('left')
                    ->formPanelWidth('40%')
                    ->emptyPanelBackgroundImageOpacity('35%')
                    ->emptyPanelBackgroundImageUrl(asset('img/auth-bg.png'))
            )
            ->plugin(
                DrilldownSidebarPlugin::make()
                    ->drilledGroups([
                        __('admin.nav_groups.crm', [], 'en'),
                        __('admin.nav_groups.lms_management', [], 'en'),
                        __('admin.nav_groups.commerce', [], 'en'),
                        __('admin.nav_groups.cms', [], 'en'),
                        __('admin.nav_groups.users_access', [], 'en'),
                        __('admin.nav_groups.settings', [], 'en'),
                        __('admin.nav_groups.crm', [], 'ar'),
                        __('admin.nav_groups.lms_management', [], 'ar'),
                        __('admin.nav_groups.commerce', [], 'ar'),
                        __('admin.nav_groups.cms', [], 'ar'),
                        __('admin.nav_groups.users_access', [], 'ar'),
                        __('admin.nav_groups.settings', [], 'ar'),
                    ])
            )
            ->plugin(
                FilamentLogViewerPlugin::make()
                    ->navigationGroup(__('admin.nav_groups.settings'))
                    ->navigationSort(2)
                    // ->navigationIcon(Heroicon::OutlinedDocumentText)

                    ->navigationLabel('Log Viewer')
                    ->authorize(fn (): bool => auth('admin')->user()?->can('log_viewer.viewAny') ?? true)
            )
            ->navigationGroups([
                NavigationGroup::make(__('admin.nav_groups.crm'))->collapsed(),
                NavigationGroup::make(__('admin.nav_groups.lms_management'))->collapsed(),
                NavigationGroup::make(__('admin.nav_groups.commerce'))->collapsed(),
                NavigationGroup::make(__('admin.nav_groups.cms'))->collapsed(),
                NavigationGroup::make(__('admin.nav_groups.users_access'))->collapsed(),
                NavigationGroup::make(__('admin.nav_groups.settings'))->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make(fn (): string => __('admin.navigation.reports'))
                    ->group(fn (): string => __('admin.nav_groups.commerce'))
                    ->icon('heroicon-o-chart-bar-square')
                    ->sort(5)
                    ->visible(fn (): bool => auth('admin')->user()?->canAny([
                        'orders_report.viewAny',
                        'sales_performance_report.viewAny',
                        'order_line_items_report.viewAny',
                        'employee_daily_order_report.viewAny',
                        'store_organic_orders_report.viewAny',
                        'course_performance_report.viewAny',
                        'marketing_source_report.viewAny',
                    ]) ?? false),
                NavigationItem::make(fn (): string => __('admin.navigation.request_reports'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-chart-bar-square')
                    ->sort(1)
                    ->visible(fn (): bool => auth('admin')->user()?->canAny([
                        'crm_reports.viewAny',
                        'employee_request_performance_report.viewAny',
                        'course_request_status_report.viewAny',
                        'service_request_status_report.viewAny',
                        'landing_version_report.viewAny',
                    ]) ?? false),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_leads_requests_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->sort(2),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_sales_pipeline_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-funnel')
                    ->sort(3),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_team_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-user-group')
                    ->sort(4),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_engagement_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sort(6),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_quality_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->sort(6)
                    ->visible(fn (): bool => auth('admin')->user()?->canAny([
                        'call_evaluations.viewAny',
                        'call_evaluations.create',
                        'call_quality_criteria.manage',
                    ]) ?? false),
                NavigationItem::make(fn (): string => __('admin.navigation.crm_automation_group'))
                    ->group(fn (): string => __('admin.nav_groups.crm'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->sort(7),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_catalog_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-book-open')
                    ->sort(1),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_curriculum_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-rectangle-stack')
                    ->sort(2),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_delivery_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-calendar')
                    ->sort(3),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_assessments_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->sort(4),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_people_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-user-group')
                    ->sort(5),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_engagement_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sort(6),
                NavigationItem::make(fn (): string => __('admin.navigation.lms_certificates_group'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-document-check')
                    ->sort(7),
                NavigationItem::make(fn (): string => __('admin.navigation.reports'))
                    ->group(fn (): string => __('admin.nav_groups.lms_management'))
                    ->icon('heroicon-o-chart-bar-square')
                    ->sort(8)
                    ->visible(fn (): bool => auth('admin')->user()?->canAny([
                        'lms_enrollment_report.viewAny',
                    ]) ?? false),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            // ->databaseNotifications()
            // ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])

            ->middleware([
                // SetAdminSessionCookie::class, // breaks Livewire: /livewire/update uses web session, not admin session
                'web',
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.components.sidebar-wave')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('filament.components.admin-topbar')
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.admin-error-handler')
            );

        // ->renderHook(PanelsRenderHook::TOPBAR_START, fn () => view('filament.components.admin-topbar'));
    }
}
