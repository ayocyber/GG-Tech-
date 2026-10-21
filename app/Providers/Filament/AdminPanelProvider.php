<?php

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Filament\Widgets\StoreStatsOverview;
use App\Http\Middleware\SetAdminLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('GG-TECH')
            ->brandLogo(asset('images/gg-tech-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/gg-tech-logo.png'))
            ->colors([
                'primary' => Color::hex('#1d2b8f'),
                'danger' => Color::hex('#d7102b'),
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->navigationGroups(NavigationGroup::class)
            ->userMenuItems([
                Action::make('english')
                    ->label('English')
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('admin.locale', 'en'))
                    ->visible(fn () => app()->getLocale() !== 'en'),
                Action::make('french')
                    ->label('Français')
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('admin.locale', 'fr'))
                    ->visible(fn () => app()->getLocale() !== 'fr'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                StoreStatsOverview::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Only the language must be re-applied on Livewire updates; persisting the
            // cookie and session middleware too decrypts cookies twice and logs staff out.
            ->middleware([
                SetAdminLocale::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
