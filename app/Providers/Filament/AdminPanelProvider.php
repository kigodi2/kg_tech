<?php

namespace App\Providers\Filament;

use App\Helpers\SystemSettingsHelper;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Log;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $brandName = $this->setting('dashboard_identity', 'IRMS Admin Panel');
        $primaryColor = $this->setting('primary_color', '#00A3DD');
        $fontFamily = $this->setting('font_family', 'Ubuntu');
        $systemLogo = $this->setting('system_logo');
        $brandLogo = $systemLogo ? asset('storage/' . ltrim((string) $systemLogo, '/')) : asset('images/logo.png');

        return $panel
            // ========== BASIC CONFIGURATION ==========
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->homeUrl('/admin/dashboard')
            
            // ========== BRANDING & APPEARANCE ==========
            ->brandName($brandName)
            ->brandLogo($brandLogo)
            ->brandLogoHeight('2.5rem')
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Dark)
            ->favicon(asset('images/emblem.png'))
            
            // ========== COLORS & STYLING ==========
            ->colors([
                'primary' => $primaryColor, // configurable IRMS primary
                'danger'  => '#ef4444', // red
                'warning' => '#FCD116', // tz-yellow
                'success' => '#1EB53A', // tz-green
                'gray'    => [
                    50  => '#f0f4f7',
                    100 => '#e0e8f0',
                    200 => '#c0ccd6',
                    300 => '#a0b0bf',
                    400 => '#8094a8',
                    500 => '#607891',
                    600 => '#405c7a',
                    700 => '#2a3040',
                    800 => '#101518', // card bg
                    900 => '#0b1014', // body bg
                    950 => '#050a0d', // sidebar/nav bg
                ],
            ])
            ->font($fontFamily)
            
            // ========== LAYOUT & PERFORMANCE ==========
            ->maxContentWidth(MaxWidth::Full)
            ->unsavedChangesAlerts()              // Warn before losing changes
            ->databaseTransactions()              // Wrap operations in transactions
            
            // ========== LOCALIZATION ==========
            // Timezone set in config/app.php
            
            // ========== RESOURCE DISCOVERY ==========
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages'
            )
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Admin/Widgets'),
                for: 'App\\Filament\\Admin\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Admin Control Centre')
                    ->url('/admin/dashboard')
                    ->icon('heroicon-o-squares-2x2')
                    ->group('System')
                    ->sort(1),

                \Filament\Navigation\NavigationItem::make('Mock Portal')
                    ->url('/mock-portal')
                    ->icon('heroicon-o-globe-alt')
                    ->group('REGISTRATION')
                    ->sort(100),
            ])
            
            // ========== MIDDLEWARE STACK ==========
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                \App\Http\Middleware\CheckSystemMaintenanceMode::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            
            // ========== LIFECYCLE HOOKS ==========
            ->bootUsing(function (Panel $panel) {
                // Log admin panel access for audit trail
                if (auth()->check()) {
                    Log::channel('admin')->info('Admin panel accessed', [
                        'user_id'    => auth()->id(),
                        'name'       => auth()->user()->name,
                        'role'       => auth()->user()->portal_role ?? 'unknown',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'timestamp'  => now(),
                    ]);
                }
            });
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        try {
            return SystemSettingsHelper::getSetting($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
