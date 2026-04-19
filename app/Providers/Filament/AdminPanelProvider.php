<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
        return $panel
            // ========== BASIC CONFIGURATION ==========
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->homeUrl('/')
            
            // ========== BRANDING & APPEARANCE ==========
            ->brandName('IRMS Admin Panel')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->darkMode(false)
            ->favicon(asset('favicon.ico'))
            
            // ========== COLORS & STYLING ==========
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'success' => Color::Green,
            ])
            ->font('Ubuntu')  // Ubuntu font for modern, clean UI
            
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
            
            // ========== MIDDLEWARE STACK ==========
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
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
}
